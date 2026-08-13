<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../conf.php';
require_once '../../auth_check.php';
require_once '../bpjs_api_helper.php';

check_module_access('bpjs_mapping_obat_apotek');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'load_table') {
        $draw   = intval($_GET['draw'] ?? 1);
        $start  = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        if ($length < 1 || $length > 100) $length = 10;
        $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

        $whereSql = "";
        $params = [];
        if ($search !== '') {
            $whereSql = " WHERE db.kode_brng LIKE :search
                           OR db.nama_brng LIKE :search
                           OR mb.kode_brng_apotek_bpjs LIKE :search
                           OR mb.nama_brng_apotek_bpjs LIKE :search ";
            $params[':search'] = '%' . $search . '%';
        }

        // databarang has NO 'satuan' column; unit name lives in kodesatuan.satuan (via kode_sat)
        $countSql = "SELECT COUNT(DISTINCT db.kode_brng) AS total
                     FROM databarang db
                     LEFT JOIN maping_obat_apotek_bpjs mb ON db.kode_brng = mb.kode_brng
                     " . $whereSql;
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $totalRecords = (int) $stmtCount->fetch()['total'];

        $dataSql = "SELECT
                        db.kode_brng,
                        db.nama_brng,
                        ks.satuan AS satuan,
                        MAX(mb.kode_brng_apotek_bpjs) AS kode_brng_apotek_bpjs,
                        MAX(mb.nama_brng_apotek_bpjs) AS nama_brng_apotek_bpjs
                    FROM databarang db
                    LEFT JOIN maping_obat_apotek_bpjs mb ON db.kode_brng = mb.kode_brng
                    LEFT JOIN kodesatuan ks ON db.kode_sat = ks.kode_sat
                    $whereSql
                    GROUP BY db.kode_brng, db.nama_brng, ks.satuan
                    ORDER BY db.nama_brng ASC
                    LIMIT :start, :length";

        $stmtData = $pdo->prepare($dataSql);
        foreach ($params as $k => $v) {
            $stmtData->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmtData->bindValue(':start', $start, PDO::PARAM_INT);
        $stmtData->bindValue(':length', $length, PDO::PARAM_INT);
        $stmtData->execute();

        $rows = $stmtData->fetchAll();

        $data = [];
        foreach ($rows as $i => $row) {
            $kode_safe = htmlspecialchars($row['kode_brng'], ENT_QUOTES, 'UTF-8');
            $nama_safe = htmlspecialchars($row['nama_brng'], ENT_QUOTES, 'UTF-8');

            $mapped = !empty($row['kode_brng_apotek_bpjs']);

            $status = $mapped
                ? '<span class="badge badge-mapped"><i class="fa fa-check me-1"></i>Mapped</span>'
                : '<span class="badge badge-unmapped"><i class="fa fa-xmark me-1"></i>Belum</span>';

            if ($mapped) {
                $info = '<b>Kode BPJS:</b> ' . htmlspecialchars($row['kode_brng_apotek_bpjs'], ENT_QUOTES, 'UTF-8')
                      . '<br><small class="text-muted">' . htmlspecialchars($row['nama_brng_apotek_bpjs'], ENT_QUOTES, 'UTF-8') . '</small>';
            } else {
                $info = '<small class="text-muted">- Belum dimapping -</small>';
            }

            $btn = "<button class='btn btn-sm btn-primary btn-map me-1' data-idx='" . $i . "'><i class='fa fa-edit me-1'></i>Mapping</button>";
            if ($mapped) {
                $btn .= "<button class='btn btn-sm btn-outline-danger btn-delete' data-idx='" . $i . "'><i class='fa fa-trash'></i></button>";
            }

            $data[] = [
                'kode_brng'             => $kode_safe,
                'nama_brng'             => $nama_safe,
                'kode_brng_apotek_bpjs' => $row['kode_brng_apotek_bpjs'] ?? '',
                'nama_brng_apotek_bpjs' => $row['nama_brng_apotek_bpjs'] ?? '',
                'info'                  => $info,
                'status'                => $status,
                'btn'                   => $btn,
            ];
        }

        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecords,
            "data" => $data
        ]);
        exit;
    }

    if ($action === 'search_local_drug') {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            echo json_encode(['results' => []]);
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT kode_brng AS id, CONCAT(kode_brng, ' - ', nama_brng) AS text, nama_brng
             FROM databarang
             WHERE kode_brng LIKE :q OR nama_brng LIKE :q
             ORDER BY nama_brng ASC LIMIT 20"
        );
        $stmt->execute([':q' => '%' . $q . '%']);
        echo json_encode(['results' => $stmt->fetchAll()]);
        exit;
    }

    if ($action === 'search_bpjs_ref') {
        $keyword = trim($_GET['keyword'] ?? '');
        $jenis   = trim($_GET['jenis'] ?? '1');
        $tgl     = date('Y-m-d');

        if ($keyword === '') {
            echo json_encode(['status' => 'error', 'message' => 'Kata kunci tidak boleh kosong.']);
            exit;
        }

        $api = new BpjsApiHelper();
        $res = $api->request("/referensi/obat/{$jenis}/{$tgl}/" . rawurlencode($keyword), 'GET');

        if ($res['code'] === '200' && !empty($res['data'])) {
            $list = $res['data']['list'] ?? $res['data'];
            echo json_encode(['status' => 'success', 'data' => $list]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $res['message'] ?? 'Data referensi obat BPJS tidak ditemukan.']);
        }
        exit;
    }

    if ($action === 'select_bpjs') {
        $q = trim($_GET['term'] ?? '');
        if ($q === '') {
            echo json_encode(['results' => []]);
            exit;
        }
        $jenis = trim($_GET['jenis'] ?? '1');
        $tgl   = date('Y-m-d');

        // Prioritas sumber data:
        //  1. Cache DPHO lokal jika masih segar (<= 7 hari sejak sinkronisasi terakhir)
        //  2. API BPJS real-time jika cache basi/kosong
        //  3. Cache DPHO yang basi (daripada kosong) jika API gagal
        //  4. Tabel mapping yang sudah tersimpan sebagai jalan terakhir
        $FRESH_DAYS   = 7;
        $cacheCnt     = 0;
        $cacheFresh   = false;
        $lastSync     = null;
        try {
            $cacheCnt = (int)$pdo->query("SELECT COUNT(*) FROM bpjs_ref_dpho")->fetchColumn();
            if ($cacheCnt > 0) {
                $lastSync = $pdo->query("SELECT MAX(updated_at) FROM bpjs_ref_dpho")->fetchColumn();
                $cacheFresh = ($lastSync !== null && $lastSync !== false
                    && strtotime($lastSync) >= (time() - $FRESH_DAYS * 86400));
            }
        } catch (Exception $e) {}

        $results = [];
        $dbSearch = function ($sql, $q) use ($pdo) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':q' => '%' . $q . '%']);
            $out = [];
            foreach ($stmt->fetchAll() as $row) {
                $out[] = [
                    'id'   => $row['kode'],
                    'text' => $row['kode'] . ' - ' . $row['nama'],
                    'kode' => $row['kode'],
                    'nama' => $row['nama'],
                ];
            }
            return $out;
        };

        // 1) Cache lokal masih segar → langsung dari DB (tanpa API)
        if ($cacheFresh) {
            $results = $dbSearch(
                "SELECT kodeobat AS kode, namaobat AS nama
                 FROM bpjs_ref_dpho
                 WHERE kodeobat LIKE :q OR namaobat LIKE :q
                 ORDER BY namaobat ASC
                 LIMIT 20",
                $q
            );
            echo json_encode([
                'results'    => $results,
                'source'     => 'database',
                'pagination' => ['more' => false]
            ]);
            exit;
        }

        // 2) Cache basi/kosong → coba API BPJS real-time
        $isFallback = false;
        try {
            $api = new BpjsApiHelper();
            $res = $api->request("/referensi/obat/{$jenis}/{$tgl}/" . rawurlencode($q), 'GET');
            if ($res['code'] === '200' && !empty($res['data'])) {
                $list = $res['data']['list'] ?? $res['data'];
                $seen = [];
                if (is_array($list)) {
                    foreach ($list as $item) {
                        $kode = (string)($item['kode'] ?? $item['kodeobat'] ?? '');
                        $nama = (string)($item['nama'] ?? $item['namaobat'] ?? '');
                        if ($kode === '') continue;
                        if (isset($seen[$kode])) continue; // BPJS bisa mengembalikan kodeobat duplikat
                        $seen[$kode] = true;
                        $results[] = [
                            'id'   => $kode,
                            'text' => $kode . ' - ' . $nama,
                            'kode' => $kode,
                            'nama' => $nama,
                        ];
                    }
                }
                echo json_encode([
                    'results'    => $results,
                    'source'     => 'api',
                    'pagination' => ['more' => false]
                ]);
                exit;
            }
            $isFallback = true;
        } catch (Exception $e) {
            $isFallback = true;
        }

        // 3) API gagal → pakai cache DPHO yang ada (walau basi), lalu mapping tersimpan
        if ($cacheCnt > 0) {
            $results = $dbSearch(
                "SELECT kodeobat AS kode, namaobat AS nama
                 FROM bpjs_ref_dpho
                 WHERE kodeobat LIKE :q OR namaobat LIKE :q
                 ORDER BY namaobat ASC
                 LIMIT 20",
                $q
            );
        }

        if (empty($results)) {
            $results = $dbSearch(
                "SELECT kode_brng_apotek_bpjs AS kode, nama_brng_apotek_bpjs AS nama
                 FROM maping_obat_apotek_bpjs
                 WHERE kode_brng_apotek_bpjs LIKE :q OR nama_brng_apotek_bpjs LIKE :q
                 GROUP BY kode_brng_apotek_bpjs, nama_brng_apotek_bpjs
                 ORDER BY nama_brng_apotek_bpjs ASC
                 LIMIT 20",
                $q
            );
        }

        echo json_encode([
            'results'    => $results,
            'source'     => $isFallback ? 'fallback' : 'database',
            'pagination' => ['more' => false]
        ]);
        exit;
    }

    if ($action === 'sync_dpho') {
        validate_csrf();

        // Hanya Super Admin yang boleh menyinkronkan cache DPHO
        if (empty($_SESSION['is_admin'])) {
            echo json_encode(['status' => 'error', 'message' => 'Aksi ini hanya dapat dilakukan oleh Super Admin.']);
            exit;
        }

        // Pastikan tabel cache DPHO ada (idempotent)
        $sqlFile = __DIR__ . '/../../tambahan_table/bpjs_ref_dpho.sql';
        if (is_file($sqlFile)) {
            $pdo->exec(file_get_contents($sqlFile));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File skema bpjs_ref_dpho.sql tidak ditemukan.']);
            exit;
        }

        $api = new BpjsApiHelper();
        $res = $api->request('/referensi/dpho', 'GET');

        if ($res['code'] !== '200' || empty($res['data'])) {
            echo json_encode(['status' => 'error', 'message' => $res['message'] ?? 'Gagal mengambil DPHO dari BPJS.']);
            exit;
        }

        $list = $res['data']['list'] ?? $res['data'];
        if (!is_array($list)) {
            echo json_encode(['status' => 'error', 'message' => 'Format respons DPHO tidak dikenali.']);
            exit;
        }

        // Normalisasi baris (validasi + parsing) dilakukan sekali di memori.
        // BPJS terkadang mengembalikan kodeobat duplikat dengan data yang sama
        // di semua kolom lain — ambil satu entri per kodeobat dengan harga tertinggi.
        $toBool = function ($v) {
            if (is_bool($v)) return (int)$v;
            if (is_numeric($v)) return (int)(bool)$v;
            return (int)(strtolower(trim((string)$v)) === 'true' || strtolower(trim((string)$v)) === '1');
        };

        $byKode = [];
        foreach ($list as $item) {
            $kode = trim((string)($item['kodeobat'] ?? $item['kode'] ?? ''));
            if ($kode === '') continue;

            $row = [
                $kode,
                trim((string)($item['namaobat'] ?? $item['nama'] ?? '')),
                $toBool($item['prb'] ?? false),
                $toBool($item['kronis'] ?? false),
                $toBool($item['kemo'] ?? false),
                (float)($item['harga'] ?? 0),
                trim((string)($item['restriksi'] ?? '')),
                trim((string)($item['generik'] ?? '')),
                trim((string)($item['aktif'] ?? '')),
                trim((string)($item['sedia'] ?? '')),
                trim((string)($item['stok'] ?? '')),
            ];

            // Dedup: simpan baris dengan harga tertinggi untuk kodeobat yang sama
            if (!isset($byKode[$kode]) || $row[5] > $byKode[$kode][5]) {
                $byKode[$kode] = $row;
            }
        }

        $rows = array_values($byKode);

        if (empty($rows)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada data DPHO yang valid dari respons BPJS.']);
            exit;
        }

        // Tulis batch: full-refresh (hapus lama + insert massal) dalam satu transaksi.
        // Satu pernyataan multi-row per batch = jauh lebih cepat daripada insert per baris.
        $cols = 'kodeobat, namaobat, prb, kronis, kemo, harga, restriksi, generik, aktif, sedia, stok';
        $batchSize = 500;

        $pdo->beginTransaction();
        try {
            $pdo->exec("DELETE FROM bpjs_ref_dpho");

            foreach (array_chunk($rows, $batchSize) as $chunk) {
                $rowPlaceholder = '(' . implode(',', array_fill(0, count($chunk[0]), '?')) . ')';
                $placeholders = implode(',', array_fill(0, count($chunk), $rowPlaceholder));
                $stmt = $pdo->prepare("INSERT INTO bpjs_ref_dpho ($cols) VALUES $placeholders");

                $flat = [];
                foreach ($chunk as $row) {
                    foreach ($row as $v) $flat[] = $v;
                }
                $stmt->execute($flat);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        $count = count($rows);
        echo json_encode(['status' => 'success', 'message' => $count . ' data DPHO berhasil disinkronkan.', 'count' => $count]);
        exit;
    }

    if ($action === 'search_bpjs_dpho') {
        $keyword = trim($_GET['keyword'] ?? '');

        $cacheCnt = 0;
        try { $cacheCnt = (int)$pdo->query("SELECT COUNT(*) FROM bpjs_ref_dpho")->fetchColumn(); } catch (Exception $e) {}
        if ($cacheCnt === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cache DPHO masih kosong. Klik tombol "Muat DPHO" untuk menyinkronkan data dari BPJS.']);
            exit;
        }

        $sql = "SELECT kodeobat AS kode, namaobat AS nama, harga, restriksi, prb, kronis, kemo, sedia, stok, generik, aktif
                FROM bpjs_ref_dpho";
        $params = [];
        if ($keyword !== '') {
            $sql .= " WHERE kodeobat LIKE :k OR namaobat LIKE :k OR restriksi LIKE :k";
            $params[':k'] = '%' . $keyword . '%';
        }
        $sql .= " ORDER BY namaobat ASC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        foreach ($list as &$row) {
            $row['harga']  = (float)$row['harga'];
            $row['prb']    = (bool)$row['prb'];
            $row['kronis'] = (bool)$row['kronis'];
            $row['kemo']   = (bool)$row['kemo'];
        }
        unset($row);

        echo json_encode(['status' => 'success', 'data' => $list]);
        exit;
    }

    if ($action === 'dpho_detail') {
        $kode = trim($_GET['kode'] ?? '');
        if ($kode === '') {
            echo json_encode(['status' => 'error', 'message' => 'Kode obat tidak valid.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM bpjs_ref_dpho WHERE kodeobat = ? LIMIT 1");
            $stmt->execute([$kode]);
            $row = $stmt->fetch();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel cache DPHO belum ada. Klik "Muat DPHO" terlebih dahulu.']);
            exit;
        }

        if (!$row) {
            echo json_encode(['status' => 'success', 'data' => null]);
            exit;
        }

        $row['harga']  = (float)$row['harga'];
        $row['prb']    = (bool)$row['prb'];
        $row['kronis'] = (bool)$row['kronis'];
        $row['kemo']   = (bool)$row['kemo'];

        echo json_encode(['status' => 'success', 'data' => $row]);
        exit;
    }

    if ($action === 'save_mapping') {
        validate_csrf();

        $kode_brng              = trim($_POST['kode_brng'] ?? '');
        $kode_brng_apotek_bpjs  = trim($_POST['kode_brng_apotek_bpjs'] ?? '');
        $nama_brng_apotek_bpjs  = trim($_POST['nama_brng_apotek_bpjs'] ?? '');

        if ($kode_brng === '' || $kode_brng_apotek_bpjs === '') {
            echo json_encode(['status' => 'error', 'message' => 'Kode Barang RS dan Kode Obat BPJS wajib diisi.']);
            exit;
        }

        // Kode BPJS harus numerik
        if (!preg_match('/^[0-9]+$/', $kode_brng_apotek_bpjs)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode Obat BPJS harus berupa angka.']);
            exit;
        }

        $sql = "INSERT INTO maping_obat_apotek_bpjs (kode_brng, kode_brng_apotek_bpjs, nama_brng_apotek_bpjs)
                VALUES (:kode_brng, :kode_bpjs, :nama_bpjs)
                ON DUPLICATE KEY UPDATE
                    kode_brng_apotek_bpjs = VALUES(kode_brng_apotek_bpjs),
                    nama_brng_apotek_bpjs = VALUES(nama_brng_apotek_bpjs)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kode_brng' => $kode_brng,
            ':kode_bpjs' => $kode_brng_apotek_bpjs,
            ':nama_bpjs' => $nama_brng_apotek_bpjs
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Mapping obat BPJS berhasil disimpan.']);
        exit;
    }

    if ($action === 'delete_mapping') {
        validate_csrf();

        $kode_brng = trim($_POST['kode_brng'] ?? '');
        if ($kode_brng === '') {
            echo json_encode(['status' => 'error', 'message' => 'Kode Barang RS tidak valid.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM maping_obat_apotek_bpjs WHERE kode_brng = :kode_brng");
        $stmt->execute([':kode_brng' => $kode_brng]);

        echo json_encode(['status' => 'success', 'message' => 'Mapping obat BPJS berhasil dihapus.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
