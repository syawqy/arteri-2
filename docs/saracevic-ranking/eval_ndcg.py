#!/usr/bin/env python3
"""
eval_ndcg.py — hitung P@10 & NDCG@10 untuk Arteri Saracevic ranking

Input: relevance-judgment CSV dengan kolom:
  query_id, arsip_id, rank_baseline, rank_saracevic,
  relevance_system, relevance_topical, relevance_cognitive, relevance_situational, relevance_motivational,
  overall_relevance_0_3, catatan_penilai

- overall_relevance_0_3 (0-3) dipakai sebagai gain untuk NDCG
- P@10 = (# relevan overall>=2 di top-10) / 10
- NDCG@10 = DCG@10 / IDCG@10, gain = 2^rel -1 atau rel

Usage:
  python docs/saracevic-ranking/eval_ndcg.py docs/saracevic-ranking/relevance-judgment.csv
  python docs/saracevic-ranking/eval_ndcg.py --gain exp2 relevance-judgment.csv
  python docs/saracevic-ranking/eval_ndcg.py --synthetic       # generate synthetic dari DB via PDO+service (demo tanpa human judge)
  python docs/saracevic-ranking/eval_ndcg.py --synthetic --k 10
"""
import argparse, csv, math, sys, pathlib, re, sqlite3
from collections import defaultdict
from datetime import date, datetime

def dcg(relevances, k=10, gain='linear'):
    s=0.0
    for i, rel in enumerate(relevances[:k]):
        g = (2**rel -1) if gain=='exp2' else rel
        s += g / math.log2(i+2)
    return s

def parse_args():
    p=argparse.ArgumentParser()
    p.add_argument('csvfile', nargs='?', help='relevance judgment CSV')
    p.add_argument('--gain', choices=['linear','exp2'], default='linear')
    p.add_argument('--k', type=int, default=10)
    p.add_argument('--synthetic', action='store_true', help='generate synthetic judgment from DB (demo)')
    return p.parse_args()

def load_judgment(path):
    rows=[]
    with open(path, newline='', encoding='utf-8-sig') as f:
        r=csv.DictReader(f)
        if r.fieldnames: r.fieldnames=[h.strip() for h in r.fieldnames]
        for row in r:
            if not (row.get('query_id') or '').strip(): continue
            rows.append({k:(v.strip() if isinstance(v,str) else v) for k,v in row.items()})
    return rows

def evaluate(rows, k=10, gain='linear'):
    by_q = defaultdict(list)
    for r in rows: by_q[r['query_id']].append(r)
    results=[]
    for qid, lst in sorted(by_q.items()):
        def rank_of(r, key):
            v=r.get(key,'')
            try: return int(v) if str(v).strip()!='' and str(v).strip()!='-' else 999
            except: return 999
        def overall(r):
            try: return int(str(r.get('overall_relevance_0_3','')).strip() or 0)
            except: return 0
        baseline_sorted = sorted(lst, key=lambda r: rank_of(r,'rank_baseline'))
        ranked_sorted   = sorted(lst, key=lambda r: rank_of(r,'rank_saracevic'))
        b_rel = [overall(r) for r in baseline_sorted if rank_of(r,'rank_baseline')<=k]
        s_rel = [overall(r) for r in ranked_sorted   if rank_of(r,'rank_saracevic')<=k]
        b_rel = (b_rel + [0]*k)[:k]
        s_rel = (s_rel + [0]*k)[:k]
        p_b = sum(1 for x in b_rel if x>=2)/k
        p_s = sum(1 for x in s_rel if x>=2)/k
        all_rel = sorted([overall(r) for r in lst], reverse=True)[:k]
        ideal = dcg(all_rel, k, gain)
        dcg_b = dcg(b_rel, k, gain)
        dcg_s = dcg(s_rel, k, gain)
        ndcg_b = (dcg_b/ideal) if ideal>0 else 0.0
        ndcg_s = (dcg_s/ideal) if ideal>0 else 0.0
        results.append((qid, p_b, p_s, ndcg_b, ndcg_s, b_rel, s_rel, lst))
    if results:
        avg_pb = sum(r[1] for r in results)/len(results)
        avg_ps = sum(r[2] for r in results)/len(results)
        avg_nb = sum(r[3] for r in results)/len(results)
        avg_ns = sum(r[4] for r in results)/len(results)
    else:
        avg_pb=avg_ps=avg_nb=avg_ns=0
    print(f"# Evaluasi Saracevic Ranking — P@{k} & NDCG@{k} (gain={gain})")
    print(f"Query, P@{k}_baseline, P@{k}_ranked, ΔP, NDCG@{k}_baseline, NDCG@{k}_ranked, ΔNDCG")
    for qid, pb, ps, nb, ns, _, _, _ in results:
        print(f"{qid}, {pb:.3f}, {ps:.3f}, {ps-pb:+.3f}, {nb:.3f}, {ns:.3f}, {ns-nb:+.3f}")
    print(f"AVG, {avg_pb:.3f}, {avg_ps:.3f}, {avg_ps-avg_pb:+.3f}, {avg_nb:.3f}, {avg_ns:.3f}, {avg_ns-avg_nb:+.3f}")
    print(f"\nInterpretasi: ΔP@{k}={avg_ps-avg_pb:+.3f}, ΔNDCG@{k}={avg_ns-avg_nb:+.3f} (positif = ranked lebih baik)")
    return results

# ---- Python port of RelevanceRankingService ----
def tokenize(kw: str):
    kw = kw.strip().lower()
    if not kw: return []
    parts = re.split(r'\s+', kw)
    seen=set(); out=[]
    for p in parts:
        p=p.strip()
        if p and p not in seen:
            seen.add(p); out.append(p)
    return out

def score_relativeness(row, tokens):
    if not tokens: return 0.0
    uraian = (row.get('uraian') or '').lower()
    noarsip = (row.get('noarsip') or '').lower()
    nobox = (row.get('nobox') or '').lower()
    pencipta = (row.get('nama_pencipta') or '').lower()
    pengolah = (row.get('nama_pengolah') or '').lower()
    kode_nama = ((row.get('nama_kode') or '') + ' ' + (row.get('nama') or '')).lower().strip()
    score=0.0
    for tok in tokens:
        tok_score=0.0
        if tok in uraian:
            # word boundary bonus
            if re.search(r'\b' + re.escape(tok) + r'\b', uraian):
                tok_score = max(tok_score, 3.0)
            else:
                tok_score = max(tok_score, 1.5)
        if tok in noarsip: tok_score = max(tok_score, 2.0)
        if tok in nobox: tok_score = max(tok_score, 1.0)
        if tok in pencipta: tok_score = max(tok_score, 1.5)
        if tok in pengolah: tok_score = max(tok_score, 1.5)
        if tok in kode_nama: tok_score = max(tok_score, 1.5)
        score += tok_score
    max_score = len(tokens)*3.0
    return min(1.0, score/max_score) if max_score>0 else 0.0

def score_timeliness(row):
    today = date.today()
    b = row.get('b')
    f = row.get('f')
    if b and b != '0000-00-00':
        try:
            expiry = datetime.strptime(b, '%Y-%m-%d').date()
            diff = abs((expiry - today).days)
            base = 1.0/(1.0 + diff/365.0)
            if f=='sudah' or expiry < today:
                base = min(1.0, base + 0.08)
            return round(min(1.0, max(0.0, base)),4)
        except: pass
    tanggal = row.get('tanggal')
    if tanggal and tanggal != '0000-00-00':
        try:
            tgl = datetime.strptime(tanggal, '%Y-%m-%d').date()
            age = abs((today - tgl).days)
            return round(1.0/(1.0 + age/(365*3)),4)
        except: return 0.5
    return 0.5

def score_relations(row, co_map, max_count):
    if not co_map or max_count<=0: return 0.0
    kode = str(row.get('kode') or '')
    pencipta = str(row.get('pencipta') or '')
    if not kode or not pencipta: return 0.0
    key = f"{kode}:{pencipta}"
    cnt = co_map.get(key,0)
    return min(1.0, cnt/max_count) if cnt>0 else 0.0

def build_co_map(rows):
    m={}
    for r in rows:
        k=str(r.get('kode') or ''); p=str(r.get('pencipta') or '')
        if not k or not p: continue
        key=f"{k}:{p}"
        m[key]=m.get(key,0)+1
    return m

def rank_rows(rows, keywords, weights=None):
    if not rows: return []
    if weights is None: weights={'rel':0.5,'time':0.3,'relasi':0.2}
    s=sum(weights.values()); weights={k:v/s for k,v in weights.items()}
    tokens=tokenize(keywords)
    # build co_map from this result set (like PHP does)
    co_map=build_co_map(rows)
    max_cnt=max(co_map.values()) if co_map else 1
    scored=[]
    for r in rows:
        r=dict(r)  # copy
        r['score_rel']=score_relativeness(r, tokens)
        r['score_time']=score_timeliness(r)
        r['score_relasi']=score_relations(r, co_map, max_cnt)
        r['score']=round(weights['rel']*r['score_rel']+weights['time']*r['score_time']+weights['relasi']*r['score_relasi'],4)
        scored.append(r)
    scored.sort(key=lambda x: (-x['score'], x.get('tanggal','')))
    # tie breaker: tanggal DESC handled by secondary key via stable sort? use reverse
    # re-sort with tuple
    scored.sort(key=lambda x: (-x['score'], x.get('tanggal') or ''), reverse=False)
    # Actually need score desc then tanggal desc
    scored.sort(key=lambda x: ( -x['score'], '' if not x.get('tanggal') else x['tanggal']), reverse=False)
    # Simpler: already sorted by score desc; Python stable so we first sort by tanggal desc then score desc
    scored.sort(key=lambda x: x.get('tanggal') or '', reverse=True)
    scored.sort(key=lambda x: x['score'], reverse=True)
    return scored

def synthetic_demo(k=10):
    """Generate synthetic judgment via Python SQLite + ranking service — no CI bootstrap."""
    root = pathlib.Path(__file__).resolve().parents[2]
    dbfile = root / 'writable' / 'database.db'
    qfile  = root / 'docs' / 'saracevic-ranking' / 'query-set-30.csv'
    out_csv = pathlib.Path(__file__).parent / 'relevance-judgment-synthetic.csv'
    if not dbfile.exists():
        print(f"DB not found: {dbfile}", file=sys.stderr); sys.exit(1)
    con = sqlite3.connect(str(dbfile))
    con.row_factory = sqlite3.Row
    queries=[]
    with open(qfile, newline='', encoding='utf-8-sig') as f:
        import csv as csvm
        r=csvm.DictReader(f)
        for row in r:
            if not row.get('query_id'): continue
            queries.append((row['query_id'], row['keywords']))
    # Write header
    out_f = open(out_csv, 'w', newline='', encoding='utf-8')
    w=csv.writer(out_f)
    w.writerow(['query_id','arsip_id','rank_baseline','rank_saracevic','relevance_system','relevance_topical','relevance_cognitive','relevance_situational','relevance_motivational','overall_relevance_0_3','catatan_penilai'])
    total_rows=0
    for qid, kw in queries:
        like = f"%{kw}%"
        # Use same WHERE as ArsipModel: OR over noarsip/uraian/nobox, plus joins
        # For advanced queries with no match, LIKE may return 0 — still ok
        sql = """SELECT a.*, k.retensi, date(a.tanggal, '+' || k.retensi || ' years') as b,
            (CASE WHEN date(a.tanggal, '+' || k.retensi || ' years') < date('now') THEN 'sudah' ELSE 'belum' END) as f,
            k.kode as nama_kode, l.nama_lokasi, m.nama_media, p.nama_pencipta, pn.nama_pengolah, a.kode as kode, a.pencipta as pencipta
            FROM data_arsip a
            JOIN master_kode k ON k.id = a.kode
            JOIN master_lokasi l ON l.id = a.lokasi
            JOIN master_media m ON m.id = a.media
            JOIN master_pencipta p ON p.id = a.pencipta
            JOIN master_pengolah pn ON pn.id = a.unit_pengolah
            WHERE a.deleted_at IS NULL AND (a.noarsip LIKE ? OR a.uraian LIKE ? OR a.nobox LIKE ?)
            ORDER BY a.id ASC LIMIT 50"""
        rows = [dict(r) for r in con.execute(sql, (like,like,like)).fetchall()]
        if not rows:
            # fallback: try token OR (split keywords)
            toks=tokenize(kw)
            if toks:
                cond=' OR '.join(['a.uraian LIKE ?']*len(toks))
                vals=[f"%{t}%" for t in toks]
                sql2 = f"""SELECT a.*, k.retensi, date(a.tanggal, '+' || k.retensi || ' years') as b,
                    (CASE WHEN date(a.tanggal, '+' || k.retensi || ' years') < date('now') THEN 'sudah' ELSE 'belum' END) as f,
                    k.kode as nama_kode, l.nama_lokasi, m.nama_media, p.nama_pencipta, pn.nama_pengolah, a.kode as kode, a.pencipta as pencipta
                    FROM data_arsip a
                    JOIN master_kode k ON k.id = a.kode
                    JOIN master_lokasi l ON l.id = a.lokasi
                    JOIN master_media m ON m.id = a.media
                    JOIN master_pencipta p ON p.id = a.pencipta
                    JOIN master_pengolah pn ON pn.id = a.unit_pengolah
                    WHERE a.deleted_at IS NULL AND ({cond})
                    ORDER BY a.id ASC LIMIT 50"""
                rows=[dict(r) for r in con.execute(sql2, vals).fetchall()]
        baseline = rows[:k]  # baseline top-k by id ASC
        ranked_all = rank_rows(rows, kw) if rows else []
        ranked = ranked_all[:k]
        # Map all ids that appear in either top-k
        all_ids={}
        for r in baseline: all_ids[r['id']] = r
        for r in ranked: all_ids[r['id']] = r
        # For each, compute rank positions
        pos_b={r['id']:i+1 for i,r in enumerate(baseline)}
        pos_s={r['id']:i+1 for i,r in enumerate(ranked)}
        # Score lookup from ranked
        score_map={r['id']:r['score'] for r in ranked_all}
        for aid, _ in all_ids.items():
            sc = score_map.get(aid, 0.2)
            overall = max(0,min(3,int(round(sc*3))))
            if overall==0 and (aid in pos_b or aid in pos_s):
                overall=1  # avoid all-zero ideal
            w.writerow([qid, aid, pos_b.get(aid,''), pos_s.get(aid,''), overall, overall, overall, overall, overall, overall, f"synthetic score={sc:.4f}"])
            total_rows+=1
    out_f.close()
    con.close()
    print(f"Synthetic CSV: {out_csv} ({out_csv.stat().st_size} bytes, {total_rows} rows)")
    return str(out_csv)

if __name__=='__main__':
    a=parse_args()
    if a.synthetic:
        csvfile = synthetic_demo(k=a.k)
        rows=load_judgment(csvfile)
        evaluate(rows, k=a.k, gain=a.gain)
    elif not a.csvfile:
        print("Usage: eval_ndcg.py <judgment.csv>  or  eval_ndcg.py --synthetic", file=sys.stderr); sys.exit(2)
    else:
        rows=load_judgment(a.csvfile)
        if not rows: print("Empty judgment file", file=sys.stderr); sys.exit(1)
        evaluate(rows, k=a.k, gain=a.gain)
