import os
from PIL import Image, ImageDraw, ImageFont

def generate_dsr_reengineering_diagram(output_path):
    width = 1600
    height = 960
    
    # Create white canvas
    img = Image.new("RGB", (width, height), "#FFFFFF")
    draw = ImageDraw.Draw(img)
    
    # Fonts
    try:
        font_title = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 28)
        font_step_title = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 23)
        font_text = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 19)
        font_badge = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 16)
    except Exception:
        font_title = ImageFont.load_default()
        font_step_title = ImageFont.load_default()
        font_text = ImageFont.load_default()
        font_badge = ImageFont.load_default()

    # Colors
    c_border = "#D0D7DE"
    c_bg_box = "#F6F8FA"
    c_header_fill = "#0969DA"
    c_text_dark = "#1F2328"
    c_arrow = "#0969DA"
    
    # Title
    draw.text((width // 2, 45), "Gambar 1. Kerangka Metodologi Rekayasa Ulang Arteri (DSR + Re-engineering Lifecycle)", fill="#1F2328", font=font_title, anchor="mm")
    
    # Box definitions (4 vertical stages)
    boxes = [
        {
            "step": "Tahap 1",
            "lifecycle": "Reverse Engineering",
            "title": "Identifikasi Masalah & Audit Sistem Warisan",
            "bullets": [
                "• Audit kode sumber Arteri-1 (CodeIgniter 3.1.x & PHP 5.6/7.x)",
                "• Pemetaan kerentanan keamanan (SQLi, hashing MD5/SHA1, non-CSRF)",
                "• Rekonstruksi model data relasional (data_arsip, master_kode, sirkulasi)",
                "• Identifikasi technical debt dan kendala kompatibilitas runtime PHP 8.x"
            ]
        },
        {
            "step": "Tahap 2",
            "lifecycle": "Restructuring",
            "title": "Perancangan Arsitektur Target & Spesifikasi Solusi",
            "bullets": [
                "• Pemisahan document root publik (public/index.php vs app/ core)",
                "• Migrasi arsitektur ke CodeIgniter 4 dengan autoloading PSR-4",
                "• Penerapan tipe data ketat (strict typing) berbasis PHP 8.4",
                "• Perancangan skrip migrasi database deklaratif (MySQL & SQLite)"
            ]
        },
        {
            "step": "Tahap 3",
            "lifecycle": "Forward Engineering",
            "title": "Rekayasa Maju & Implementasi Fitur Baru",
            "bullets": [
                "• Pengerasan keamanan OWASP Top 10 (Bcrypt, Auth Filter, Secure File)",
                "• Pembangunan modul Jejak Audit (SystemLog) & Soft Deletes (Trash)",
                "• Pembangunan antarmuka RESTful API v1 terstandar OpenAPI 3.0",
                "• Autentikasi API Key aman berbasis SHA-256 dengan rate limiting"
            ]
        },
        {
            "step": "Tahap 4",
            "lifecycle": "Evaluation",
            "title": "Demonstrasi & Evaluasi Kualitas Perangkat Lunak",
            "bullets": [
                "• Eksekusi rangkaian pengujian regresi otomatis (PHPUnit Test Suite)",
                "• Evaluasi karakteristik kualitas mengacu pada standar ISO/IEC 25010",
                "• Verifikasi mitigasi risiko keamanan terhadap matriks OWASP Top 10",
                "• Pengujian kesiapan interoperabilitas sistem kearsipan modern"
            ]
        }
    ]
    
    box_w = 1440
    box_h = 160
    start_x = 80
    start_y = 95
    gap_y = 48
    
    for i, b in enumerate(boxes):
        y = start_y + i * (box_h + gap_y)
        
        # Draw box shadow / outline
        draw.rounded_rectangle([start_x, y, start_x + box_w, y + box_h], radius=12, fill=c_bg_box, outline=c_border, width=2)
        
        # Left Accent Bar
        draw.rounded_rectangle([start_x, y, start_x + 10, y + box_h], radius=4, fill=c_header_fill)
        
        # Badge: Step & Lifecycle with proper margin from accent bar
        badge_text = f"{b['step'].upper()} — {b['lifecycle']}"
        draw.rounded_rectangle([start_x + 30, y + 16, start_x + 330, y + 46], radius=6, fill="#DDF4FF", outline="#54AEFF", width=1)
        draw.text((start_x + 180, y + 31), badge_text, fill="#0969DA", font=font_badge, anchor="mm")
        
        # Box Title
        draw.text((start_x + 348, y + 31), b['title'], fill=c_text_dark, font=font_step_title, anchor="lm")
        
        # Bullets in 2 columns
        col1_bullets = b['bullets'][:2]
        col2_bullets = b['bullets'][2:]
        
        # Col 1
        for idx, text in enumerate(col1_bullets):
            draw.text((start_x + 30, y + 68 + idx * 34), text, fill=c_text_dark, font=font_text)
            
        # Col 2
        for idx, text in enumerate(col2_bullets):
            draw.text((start_x + 750, y + 68 + idx * 34), text, fill=c_text_dark, font=font_text)
            
        # Draw connecting arrow to next box
        if i < len(boxes) - 1:
            arrow_start_y = y + box_h + 4
            arrow_end_y = arrow_start_y + gap_y - 8
            mid_x = width // 2
            
            # Line
            draw.line([mid_x, arrow_start_y, mid_x, arrow_end_y], fill=c_arrow, width=4)
            # Arrow head
            draw.polygon([
                (mid_x - 10, arrow_end_y - 12),
                (mid_x + 10, arrow_end_y - 12),
                (mid_x, arrow_end_y + 2)
            ], fill=c_arrow)
            
    # Ensure directory exists
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    img.save(output_path, "PNG", quality=95)
    print(f"Diagram updated at {output_path}")

if __name__ == "__main__":
    generate_dsr_reengineering_diagram("/home/ubuntu/arteri-2/docs/modernization/images/dsr_methodology_flow.png")
