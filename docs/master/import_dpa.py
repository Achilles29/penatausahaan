"""
Import DPA dari dpa.xlsx ke DB penatus.
Truncate dpa + dpa_detail terlebih dahulu, lalu insert fresh.
"""
import openpyxl
import pymysql
import sys

XLSX = r'C:\xampp\htdocs\penatausahaan\docs\master\dpa.xlsx'
TAHUN = 2025

db = pymysql.connect(host='localhost', user='root', db='penatus', charset='utf8mb4',
                     use_unicode=True, autocommit=False)
cur = db.cursor()

# ── Truncate ──────────────────────────────────────────────────────────────
print("Truncating dpa_detail and dpa...")
cur.execute("SET FOREIGN_KEY_CHECKS=0")
cur.execute("TRUNCATE TABLE dpa_detail")
cur.execute("TRUNCATE TABLE dpa")
cur.execute("SET FOREIGN_KEY_CHECKS=1")
db.commit()
print("Done truncate.")

# ── Build lookup maps ──────────────────────────────────────────────────────
def load_map(table, kode_col):
    cur.execute(f"SELECT {kode_col}, id FROM {table}")
    return {str(r[0]).strip(): r[1] for r in cur.fetchall()}

opd_map   = load_map('master_opd',        'kode_opd')
prog_map  = load_map('master_program',     'kode_program')
keg_map   = load_map('master_kegiatan',    'kode_kegiatan')
sk_map    = load_map('master_subkegiatan', 'kode_subkegiatan')
rek_map   = load_map('master_rekening',    'kode_rekening')
urs_map   = load_map('master_urusan',      'kode_urusan')
bid_map   = load_map('master_bidang',      'kode_bidang')

cur.execute("SELECT nama, id FROM master_sumber_dana")
sd_map = {str(r[0]).strip(): r[1] for r in cur.fetchall()}

# ── Read Excel ─────────────────────────────────────────────────────────────
wb = openpyxl.load_workbook(XLSX, data_only=True)
ws = wb.active

# Collect rows (skip row 1 empty + row 2 header)
rows = []
for i, row in enumerate(ws.iter_rows(values_only=True)):
    if i < 2 or row[0] is None:
        continue
    rows.append(row)

print(f"Excel rows to import: {len(rows)}")
if not rows:
    print("No data found!")
    sys.exit(1)

# ── Determine OPD from first row ───────────────────────────────────────────
first = rows[0]
kode_skpd = str(first[15]).strip() if first[15] else ''  # col 16 (0-idx 15)
nama_skpd = str(first[16]).strip() if first[16] else ''  # col 17

opd_id = opd_map.get(kode_skpd)
if not opd_id:
    print(f"ERROR: OPD not found for kode_skpd={kode_skpd!r}")
    print("Available OPD kodes (sample):", list(opd_map.keys())[:5])
    sys.exit(1)
print(f"OPD: {kode_skpd} -> id={opd_id} ({nama_skpd})")

# ── Create DPA header ──────────────────────────────────────────────────────
cur.execute(
    "INSERT INTO dpa (tahun, opd_id, unit_opd_kode, unit_opd_nama, sumber_file) VALUES (%s,%s,%s,%s,%s)",
    (TAHUN, opd_id, kode_skpd, nama_skpd, 'dpa.xlsx')
)
dpa_id = cur.lastrowid
print(f"DPA header created: id={dpa_id}")

# ── Insert detail rows ─────────────────────────────────────────────────────
errors = []
inserted = 0

for idx, row in enumerate(rows, start=1):
    def s(v): return str(v).strip() if v is not None else None
    def f(v): return float(v) if v is not None else 0.0

    kode_urs  = s(row[5])   # col 6
    kode_bid  = s(row[7])   # col 8
    kode_prog = s(row[9])   # col 10
    kode_keg  = s(row[11])  # col 12
    kode_sk   = s(row[13])  # col 14
    kode_rek  = s(row[19])  # col 20
    sd_text   = s(row[23])  # col 24

    urs_id  = urs_map.get(kode_urs)
    bid_id  = bid_map.get(kode_bid)
    prog_id = prog_map.get(kode_prog)
    keg_id  = keg_map.get(kode_keg)
    sk_id   = sk_map.get(kode_sk)
    rek_id  = rek_map.get(kode_rek)
    sd_id   = sd_map.get(sd_text) if sd_text else None

    for label, val, kode in [
        ('subkegiatan', sk_id, kode_sk),
        ('rekening',    rek_id, kode_rek),
    ]:
        if not val:
            errors.append(f"Row {idx+2}: {label} not found: {kode!r}")

    cur.execute("""
        INSERT INTO dpa_detail (
            dpa_id, no_urut,
            urusan_id, bidang_id, program_id, kegiatan_id, subkegiatan_id, rekening_id,
            kode_skpd, nama_skpd,
            paket_belanja, keterangan_belanja,
            sumber_dana_id, sumber_dana_text,
            nama_penerima_bantuan, kode_standar_harga, nama_standar_harga, spesifikasi,
            koefisien_murni, harga_satuan_murni, total_harga_murni,
            koefisien, harga_satuan, total_harga
        ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
    """, (
        dpa_id, idx,
        urs_id, bid_id, prog_id, keg_id, sk_id, rek_id,
        s(row[15]), s(row[16]),
        s(row[21]), s(row[22]),
        sd_id, sd_text,
        s(row[24]), s(row[25]), s(row[26]), s(row[27]),
        s(row[28]), f(row[29]), f(row[30]),
        s(row[31]), f(row[32]), f(row[33])
    ))
    inserted += 1

db.commit()
print(f"\nInserted {inserted} dpa_detail rows")
if errors:
    print(f"\nWARNINGS ({len(errors)}):")
    for e in errors[:20]:
        print(" ", e)
else:
    print("No errors!")

cur.execute("SELECT SUM(total_harga) FROM dpa_detail WHERE dpa_id=%s", (dpa_id,))
total = cur.fetchone()[0]
print(f"\nTotal pagu DPA: Rp {total:,.2f}")

cur.close()
db.close()
