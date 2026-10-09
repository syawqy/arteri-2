<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ArsipModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Service untuk penelusuran kontekstual arsip digital berbasis ICA Records in Contexts (RiC-CM v1.0).
 * Mengintegrasikan entitas RecordResource, Agent (Pencipta/Pengolah), Activity (Klasifikasi Urusan), dan Place (Lokasi).
 */
class RicContextualDiscoveryService
{
    protected ?BaseConnection $db = null;
    protected ?ArsipModel $arsipModel = null;

    // Bobot default untuk Contextual Affinity Score (CAS)
    protected float $weightAgent = 0.35;
    protected float $weightActivity = 0.45;
    protected float $weightTemporal = 0.20;
    protected float $temporalDecayDays = 365.0; // Waktu paruh temporal (dalam hari)

    public function __construct(?BaseConnection $db = null, ?ArsipModel $arsipModel = null)
    {
        $this->db = $db;
        $this->arsipModel = $arsipModel;
    }

    protected function getDb(): BaseConnection
    {
        if ($this->db === null) {
            $this->db = Database::connect();
        }
        return $this->db;
    }

    protected function getArsipModel(): ArsipModel
    {
        if ($this->arsipModel === null) {
            $this->arsipModel = new ArsipModel();
        }
        return $this->arsipModel;
    }

    /**
     * Menghitung Contextual Affinity Score (CAS) antara dua record arsip secara murni (deterministik).
     */
    public function computeAffinity(array $seed, array $candidate): array
    {
        $seedDate = !empty($seed['tanggal']) ? strtotime((string)$seed['tanggal']) : 0;
        $candDate = !empty($candidate['tanggal']) ? strtotime((string)$candidate['tanggal']) : 0;
        $diffDays = ($seedDate > 0 && $candDate > 0) ? abs($seedDate - $candDate) / (60 * 60 * 24) : 0;

        // 1. Agent Affinity (Pencipta & Pengolah)
        $agentScore = 0.0;
        if (!empty($seed['pencipta']) && !empty($candidate['pencipta']) && $candidate['pencipta'] === $seed['pencipta']) {
            $agentScore += 0.6;
        }
        if (!empty($seed['unit_pengolah']) && !empty($candidate['unit_pengolah']) && $candidate['unit_pengolah'] === $seed['unit_pengolah']) {
            $agentScore += 0.4;
        }

        // 2. Activity / Function Affinity (Kode Klasifikasi)
        $activityScore = 0.0;
        $seedKode = (string)($seed['kode'] ?? '');
        $candKode = (string)($candidate['kode'] ?? '');

        if ($seedKode !== '' && $candKode !== '') {
            if ($seedKode === $candKode) {
                $activityScore = 1.0;
            } else {
                $seedPrefix = explode('.', $seedKode)[0];
                $candPrefix = explode('.', $candKode)[0];
                if ($seedPrefix !== '' && $seedPrefix === $candPrefix) {
                    $activityScore = 0.5; // Rumpun urusan sama
                }
            }
        }

        // 3. Temporal Affinity (Exponential Decay)
        $temporalScore = ($seedDate > 0 && $candDate > 0) ? exp(-1.0 * ($diffDays / $this->temporalDecayDays)) : 0.5;

        // Total Contextual Affinity Score (CAS)
        $totalCas = ($this->weightAgent * $agentScore) +
                    ($this->weightActivity * $activityScore) +
                    ($this->weightTemporal * $temporalScore);

        return [
            'cas_score' => round($totalCas, 4),
            'agent_affinity' => round($agentScore, 4),
            'activity_affinity' => round($activityScore, 4),
            'temporal_affinity' => round($temporalScore, 4),
        ];
    }

    /**
     * Mengambil jejaring rekaman terkait (Contextually Related Records) berdasarkan berkas jangkar (seed record).
     */
    public function findRelatedRecords(int $seedArsipId, int $limit = 10): array
    {
        $seed = $this->getArsipModel()->find($seedArsipId);
        if (!$seed) {
            return [
                'seed' => [],
                'related' => [],
                'stats' => ['error' => 'Seed record not found'],
            ];
        }

        $kodePrefix = explode('.', (string)$seed['kode'])[0] ?? (string)$seed['kode'];

        $builder = $this->getDb()->table('data_arsip a')
            ->select('a.*, k.nama as nama_kode, k.retensi as retensi_jra')
            ->join('master_kode k', 'k.kode = a.kode', 'left')
            ->where('a.id !=', $seedArsipId)
            ->where('a.deleted_at', null);

        $builder->groupStart()
            ->where('a.pencipta', $seed['pencipta'])
            ->orWhere('a.unit_pengolah', $seed['unit_pengolah'])
            ->orLike('a.kode', $kodePrefix, 'after')
            ->groupEnd();

        $candidates = $builder->get()->getResultArray();
        $scoredRecords = [];

        foreach ($candidates as $candidate) {
            $scores = $this->computeAffinity($seed, $candidate);
            $candidate = array_merge($candidate, $scores);
            $scoredRecords[] = $candidate;
        }

        usort($scoredRecords, function ($a, $b) {
            return $b['cas_score'] <=> $a['cas_score'];
        });

        $topRelated = array_slice($scoredRecords, 0, $limit);

        return [
            'seed' => $seed,
            'related' => $topRelated,
            'stats' => [
                'total_candidates_evaluated' => count($candidates),
                'top_returned' => count($topRelated),
                'weights' => [
                    'agent' => $this->weightAgent,
                    'activity' => $this->weightActivity,
                    'temporal' => $this->weightTemporal,
                ],
            ],
        ];
    }

    /**
     * Membangun representasi metadata semantik dalam format JSON-LD (RiC-O RDF).
     */
    public function generateJsonLdGraph(array $arsip, array $relatedRecords = [], string $baseUrl = 'http://localhost:8082'): array
    {
        $id = $arsip['id'] ?? 0;

        $graph = [
            '@context' => [
                'ric' => 'https://www.ica.org/standards/RiC/ontology#',
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'xsd' => 'http://www.w3.org/2001/XMLSchema#',
            ],
            '@graph' => [
                [
                    '@id' => "{$baseUrl}/arsip/detail/{$id}",
                    '@type' => 'ric:Record',
                    'ric:hasRecordIdentifier' => $arsip['noarsip'] ?? '',
                    'ric:title' => $arsip['uraian'] ?? '',
                    'ric:expressedDate' => $arsip['tanggal'] ?? '',
                    'ric:hasCreator' => [
                        '@id' => "{$baseUrl}/agent/pencipta/" . urlencode((string)($arsip['pencipta'] ?? '')),
                        '@type' => 'ric:CorporateBody',
                        'rdfs:label' => $arsip['pencipta'] ?? '',
                    ],
                    'ric:hasManagingAgent' => [
                        '@id' => "{$baseUrl}/agent/pengolah/" . urlencode((string)($arsip['unit_pengolah'] ?? '')),
                        '@type' => 'ric:CorporateBody',
                        'rdfs:label' => $arsip['unit_pengolah'] ?? '',
                    ],
                    'ric:hasActivity' => [
                        '@id' => "{$baseUrl}/activity/kode/" . urlencode((string)($arsip['kode'] ?? '')),
                        '@type' => 'ric:Activity',
                        'rdfs:label' => $arsip['kode'] ?? '',
                    ],
                    'ric:hasLocation' => [
                        '@type' => 'ric:Place',
                        'rdfs:label' => $arsip['lokasi'] ?? '',
                    ],
                ]
            ],
        ];

        if (!empty($relatedRecords)) {
            $relatedNodes = [];
            foreach ($relatedRecords as $rel) {
                $relId = $rel['id'] ?? 0;
                $relatedNodes[] = [
                    '@id' => "{$baseUrl}/arsip/detail/{$relId}",
                    '@type' => 'ric:Record',
                    'ric:hasRecordIdentifier' => $rel['noarsip'] ?? '',
                    'ric:title' => $rel['uraian'] ?? '',
                    'ric:contextualAffinityScore' => $rel['cas_score'] ?? 0.0,
                ];
            }
            $graph['@graph'][0]['ric:isContextuallyRelatedTo'] = $relatedNodes;
        }

        return $graph;
    }
}
