<?php
session_start();
define('BASE_URL', '..');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title  = 'Kelola Events';
$extra_css   = [BASE_URL . '/assets/css/admin.css'];
$admin_name  = $_SESSION['user_name'] ?? 'Admin';
$initials    = strtoupper(substr($admin_name, 0, 2));

require_once __DIR__ . '/../config/koneksi.php';

$query = "
SELECT
    events.*,

    MIN(ticket_categories.harga) AS harga_mulai,

    COALESCE(SUM(ticket_categories.stok),0) AS total_stok

FROM events

LEFT JOIN ticket_categories
ON events.id_event = ticket_categories.id_event

GROUP BY events.id_event

ORDER BY events.created_at DESC
";

$result = mysqli_query($conn, $query);

$events = [];

while ($row = mysqli_fetch_assoc($result)) {

    $id_ev = $row['id_event'];

    // Ambil ticket categories untuk event ini
    $qTicket  = mysqli_query($conn, "SELECT nama_kategori, harga, stok FROM ticket_categories WHERE id_event = '$id_ev' ORDER BY id_category ASC");
    $tickets  = [];
    while ($t = mysqli_fetch_assoc($qTicket)) {
        $tickets[] = ['name' => $t['nama_kategori'], 'price' => (int)$t['harga'], 'stock' => (int)$t['stok']];
    }

    // Ambil schedule untuk event ini
    $qSched = mysqli_query($conn, "SELECT jam, kegiatan FROM event_schedule WHERE id_event = '$id_ev' ORDER BY jam ASC");
    $schedules = [];
    while ($s = mysqli_fetch_assoc($qSched)) {
        $schedules[] = ['jam' => $s['jam'], 'kegiatan' => $s['kegiatan']];
    }

    $events[] = [
      'id'          => $id_ev,
      'title'       => $row['nama_event'],
      'date'        => date('d M Y', strtotime($row['tanggal'])),
      'cat'         => $row['kategori'],
      'price'       => $row['harga_mulai'],
      'sold'        => 0,
      'slots'       => $row['total_stok'],
      'status'      => $row['status'],
      'gambar'      => $row['gambar'],
      'location'    => $row['lokasi'],
      'organizer'   => $row['organizer'],
      'description' => $row['deskripsi'],
      'time'        => date('H:i', strtotime($row['tanggal'])),
      'tags'        => $row['tags'] ?? '',
      'tickets'     => $tickets,
      'schedules'   => $schedules,
    ];
}

function fmt_price($p)
{
    return $p > 0 ? 'Rp ' . number_format($p, 0, ',', '.') : 'Gratis';
}

// Action param
$action = $_GET['action'] ?? '';
?>
<?php require_once __DIR__ . '/../templates/head.php'; ?>

<style>
/* Inline form modal */
.modal-overlay {
  display: none; position: fixed; inset: 0; z-index: 999;
  background: rgba(14,30,60,0.4); backdrop-filter: blur(4px);
  align-items: center; justify-content: center; padding: 1rem;
}
.modal-overlay.open { display: flex; }
.modal-box {
  background: #fff; border-radius: var(--r-2xl); width: 100%; max-width: 560px;
  max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-xl);
  animation: fadeUp 0.25s var(--ease-out) both;
}
.modal-header {
  padding: var(--sp-6); border-bottom: 1px solid var(--color-border);
  display: flex; align-items: center; justify-content: space-between;
}
.modal-title { font-family: var(--font-display); font-weight: 700; font-size: var(--text-xl); }
.modal-close {
  width: 34px; height: 34px; border: none; background: var(--blue-50);
  border-radius: var(--r-md); cursor: pointer; display: flex;
  align-items: center; justify-content: center; color: var(--color-text-muted);
  transition: all var(--dur-fast);
}
.modal-close:hover { background: #FEE2E2; color: var(--color-danger); }
.modal-body { padding: var(--sp-6); display: flex; flex-direction: column; gap: var(--sp-5); }
.modal-footer {
  padding: var(--sp-5) var(--sp-6); border-top: 1px solid var(--color-border);
  display: flex; gap: var(--sp-3); justify-content: flex-end;
}
</style>

<div class="admin-layout">

  <!-- Sidebar (same structure) -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand-name">EventRes</div>
      <div class="sidebar-brand-sub">Admin Panel</div>
    </div>
    <nav class="sidebar-nav">
      <div>
        <div class="sidebar-section-label">Main</div>
        <ul class="sidebar-menu">
          <li><a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
          </a></li>
          <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Lihat Website
          </a></li>
        </ul>
      </div>
      <div>
        <div class="sidebar-section-label">Manajemen</div>
        <ul class="sidebar-menu">
          <li><a href="<?= BASE_URL ?>/admin/events.php" class="sidebar-link active" data-page="events.php">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Events <span class="sidebar-link__badge"><?= count($events) ?></span>
          </a></li>
          <li><a href="<?= BASE_URL ?>/admin/reservations.php" class="sidebar-link">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/></svg>
            Reservasi
          </a></li>
          <li><a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Users
          </a></li>
        </ul>
      </div>
      <div>
        <div class="sidebar-section-label">Lainnya</div>
        <ul class="sidebar-menu">
          <li><a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-link">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Pengaturan
          </a></li>
        </ul>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div style="min-width:0;flex:1;">
          <div class="sidebar-user-name"><?= htmlspecialchars($admin_name) ?></div>
          <div class="sidebar-user-role">Super Admin</div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="sidebar-logout" onclick="return confirm('Logout?')">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="admin-main">

    <div class="admin-page-header fade-up">
      <div>
        <h1 class="admin-page-title">Kelola Events</h1>
        <p class="admin-page-sub">Tambah, edit, dan publikasikan event untuk pengguna.</p>
      </div>
      <div class="admin-page-actions">
        <button class="btn btn-primary btn-sm" onclick="resetEventForm()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Event
        </button>
      </div>
    </div>

    <!-- Filters bar -->
    <div class="table-card fade-up fade-up-d1">
      <div class="table-card__header" style="flex-wrap:wrap;gap:0.75rem;">
        <div class="table-card__title">Daftar Event (<?= count($events) ?>)</div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
          <div class="table-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" placeholder="Cari event..." id="eventSearch">
          </div>
          <select class="form-select" style="width:auto;padding:7px 12px;font-size:var(--text-xs);">
            <option>Semua Kategori</option>
            <option>Technology</option><option>Music</option><option>Design</option>
          </select>
          <select class="form-select" style="width:auto;padding:7px 12px;font-size:var(--text-xs);">
            <option>Semua Status</option>
            <option>Published</option><option>Draft</option>
          </select>
        </div>
      </div>

      <div class="table-wrap">
        <table class="admin-table" id="eventsTable">
          <thead>
            <tr>
              <th style="width:40px;"><input type="checkbox" style="accent-color:var(--color-primary)"></th>
              <th>Event</th>
              <th>Kategori</th>
              <th>Tanggal</th>
              <th>Harga</th>
              <th>Penjualan</th>
              <th>Status</th>
              <th style="text-align:center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $i => $ev):
                $pct = round($ev['sold'] / max(1, $ev['sold'] + $ev['slots']) * 100);
                ?>
              <tr>
                <td><input type="checkbox" style="accent-color:var(--color-primary)"></td>
                <td>
                  <div class="table-avatar">
                    <div class="table-avatar-img" style="border-radius:var(--r-md);font-size:1.1rem;background:var(--blue-100);">
                        <?php
                            $catIcons = [
                                'Music' => '🎵','Technology' => '💻','Design' => '🎨',
                                'Business' => '🚀','Art' => '🖼️','Culinary' => '🍜',
                                'Sport' => '⚽','Education' => '📚','Film' => '🎬',
                                'Gaming' => '🎮','Health' => '❤️','Fashion' => '👗',
                            ];
                echo $catIcons[$ev['cat']] ?? '🎟';
                ?>
                    </div>
                    <div>
                      <div class="table-name"><?= htmlspecialchars($ev['title']) ?></div>
                      <div class="table-sub">ID: EV-<?= str_pad($ev['id'], 3, '0', STR_PAD_LEFT) ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($ev['cat']) ?></span></td>
                <td style="font-size:var(--text-sm);color:var(--color-text-muted);"><?= $ev['date'] ?></td>
                <td style="font-weight:700;color:var(--color-primary);"><?= fmt_price($ev['price']) ?></td>
                <td style="min-width:120px;">
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <div style="display:flex;justify-content:space-between;font-size:var(--text-xs);">
                      <span style="font-weight:600;"><?= $ev['sold'] ?></span>
                      <span style="color:var(--color-text-light);"><?= $pct ?>%</span>
                    </div>
                    <div style="height:5px;background:var(--blue-100);border-radius:99px;overflow:hidden;">
                      <div style="height:100%;width:<?= $pct ?>%;background:var(--grad-brand);border-radius:99px;"></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge <?= $ev['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>">
                    <?= $ev['status'] === 'published' ? 'Published' : 'Draft' ?>
                  </span>
                </td>
                <td>
                <div class="table-actions" style="justify-content:center;">

                  <!-- EDIT -->
                  <a 
                    href="#"onclick='editEvent(<?= json_encode($ev) ?>)'
                    class="btn btn-outline btn-xs"
                    title="Edit"
                  >

                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>

                    Edit
                  </a>

                  <!-- DELETE -->
                  <a 
                    href="../process/event_delete.php?id=<?= $ev['id'] ?>"
                    class="btn btn-ghost btn-xs"
                    style="color:var(--color-danger)"
                    onclick="return confirm('Hapus event ini?')"
                    title="Hapus"
                  >

                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14H6L5 6"/>
                    </svg>

                  </a>

                </div>
              </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination inside table -->
      <div style="padding:var(--sp-4) var(--sp-6);border-top:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
        <span style="font-size:var(--text-xs);color:var(--color-text-muted);">Menampilkan 1–<?= count($events) ?> dari <?= count($events) ?> event</span>
        <div style="display:flex;gap:0.5rem;">
          <button class="page-btn btn-sm" disabled style="opacity:0.4;">← Prev</button>
          <button class="page-btn active btn-sm">1</button>
          <button class="page-btn btn-sm">2</button>
          <button class="page-btn btn-sm">Next →</button>
        </div>
      </div>

    </div>

  </div><!-- /.admin-main -->

  <button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle sidebar">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
</div>

<!-- ── Add Event Modal ── -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Tambah Event Baru</div>
      <button class="modal-close" onclick="closeModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form 
      action="../process/event_store.php"
      method="POST"
      enctype="multipart/form-data"
    >

      <input
          type="hidden"
          name="id_event"
          id="id_event"
      >
      <div class="modal-body">
        <div class="form-group">
    <label class="form-label">Nama Event</label>
    <input
        class="form-input"
        type="text"
        name="nama_event"
        id="edit_nama_event"
        placeholder="Masukkan nama event"
        required
    >
</div>
        <div class="form-group">
    <label>Gambar Event</label>
    <!-- Preview gambar lama (muncul saat edit) -->
    <div id="currentImageWrap" style="display:none;margin-bottom:0.75rem;">
        <p style="font-size:0.75rem;color:var(--color-text-muted);margin-bottom:0.4rem;font-weight:600;">Gambar saat ini:</p>
        <img id="currentImage" src="" alt="Gambar event"
             style="width:100%;max-height:160px;object-fit:cover;border-radius:var(--r-md);border:1.5px solid var(--blue-200);">
        <p style="font-size:0.72rem;color:var(--color-text-light);margin-top:0.3rem;">Kosongkan upload jika tidak ingin mengganti gambar.</p>
    </div>
    <input type="file" name="gambar" class="form-input">
    <div class="form-group">
    <label class="form-label">Detail Gambar</label>
    <input
        type="file"
        name="detail_gambar"
        class="form-input"
    >
</div>
</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label class="form-label">Kategori</label>
            <select class="form-select" name="category">
              <option>Technology</option><option>Music</option><option>Design</option>
              <option>Business</option><option>Art</option><option>Culinary</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Harga (Rp)</label>
            <input class="form-input" type="number" name="price" placeholder="0 = Gratis" min="0">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label class="form-label">Tanggal Event</label>
            <input class="form-input" type="date" name="event_date" required>
          </div>
          <div class="form-group">
            <label class="form-label">Waktu</label>
            <input class="form-input" type="time" name="event_time">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Lokasi</label>
          <input class="form-input" type="text" name="location" id="edit_location" placeholder="Nama venue, kota...">
        </div>
        <div class="form-group">
    <label class="form-label">Organizer</label>
    <input
        class="form-input"
        type="text"
        name="organizer"
        id="edit_organizer"
        placeholder="Nama organizer"
    >
</div>
        <div class="form-group">
          <label class="form-label">Deskripsi</label>
          <textarea class="form-textarea" name="description" id="edit_description" rows="4" placeholder="Deskripsi singkat event..."></textarea>
        </div>
        <div class="form-group">
    <label class="form-label">Event Tags</label>

    <input
        class="form-input"
        type="text"
        name="tags"
        placeholder="music, concert, festival"
    >

    <small style="color:#94A3B8;">
        Pisahkan dengan koma
    </small>
</div>
        <div class="form-group">
          <label class="form-label">Status Publikasi</label>
          <select class="form-select" name="status" >
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          <option value="archived">Archived</option>
          </select>
        </div>
        <hr>

<hr>

<div style="
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:1rem;
">

    <h3 style="font-size:18px;">
        Ticket Category
    </h3>

    <button
        type="button"
        id="addTicket"
        class="btn btn-outline btn-sm"
    >
        + Tambah Ticket
    </button>

</div>

<div id="ticketWrapper">

    <div class="ticket-item"
    style="
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:1rem;
    margin-bottom:1rem;
    ">

        <input
            class="form-input"
            type="text"
            name="ticket_name[]"
            placeholder="VIP"
        >

        <input
            class="form-input"
            type="number"
            name="ticket_price[]"
            placeholder="500000"
        >

        <input
            class="form-input"
            type="number"
            name="ticket_stock[]"
            placeholder="100"
        >

    </div>

</div>


<hr>
<div style="
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:1rem;
">

    <h3 style="font-size:18px;">
        Event Schedule
    </h3>

    <button
        type="button"
        id="addSchedule"
        class="btn btn-outline btn-sm"
    >
        + Tambah Jadwal
    </button>

</div>

<div id="scheduleWrapper">

    <div class="schedule-item"
    style="
    display:grid;
    grid-template-columns:1fr 2fr;
    gap:1rem;
    margin-bottom:1rem;
    ">

        <input
            type="text"
            name="schedule_jam[]"
            class="form-input"
            placeholder="08:00"
        >

        <input
            type="text"
            name="schedule_kegiatan[]"
            class="form-input"
            placeholder="Open Gate"
        >

    </div>

</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Event</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit modal (same structure) -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Edit Event</div>
      <button class="modal-close" onclick="closeModal('editModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="color:var(--color-text-muted);font-size:var(--text-sm);">
        Formulir edit event akan diisi otomatis dari data yang dipilih (via PHP/AJAX).
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('editModal')">Batal</button>
      <button class="btn btn-primary">Simpan Perubahan</button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
<script>
function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) closeModal(el.id); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(el => closeModal(el.id));
});

// Live search
const search = document.getElementById('eventSearch');
if (search) {
  search.addEventListener('input', () => {
    const q = search.value.toLowerCase();
    document.querySelectorAll('#eventsTable tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}
/*
|--------------------------------------------------------------------------
| ADD TICKET CATEGORY
|--------------------------------------------------------------------------
*/

document
.getElementById('addTicket')
.addEventListener('click', function(){

    let html = `

    <div class="ticket-item"
    style="
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:1rem;
    margin-bottom:1rem;
    ">

        <input
            class="form-input"
            type="text"
            name="ticket_name[]"
            placeholder="VIP"
        >

        <input
            class="form-input"
            type="number"
            name="ticket_price[]"
            placeholder="500000"
        >

        <input
            class="form-input"
            type="number"
            name="ticket_stock[]"
            placeholder="100"
        >

    </div>

    `;

    document
    .getElementById('ticketWrapper')
    .insertAdjacentHTML('beforeend', html);

});

/*
|--------------------------------------------------------------------------
| ADD EVENT SCHEDULE
|--------------------------------------------------------------------------
*/

document
.getElementById('addSchedule')
.addEventListener('click', function(){

    let html = `

    <div class="schedule-item"
    style="
    display:grid;
    grid-template-columns:1fr 2fr;
    gap:1rem;
    margin-bottom:1rem;
    ">

        <input
            type="text"
            name="schedule_jam[]"
            class="form-input"
            placeholder="08:00"
        >

        <input
            type="text"
            name="schedule_kegiatan[]"
            class="form-input"
            placeholder="Performance"
        >

    </div>

    `;

    document
    .getElementById('scheduleWrapper')
    .insertAdjacentHTML('beforeend', html);

});


function editEvent(data)
{
    // Reset form dulu agar field lama tidak tersisa
    document.querySelector('#addModal form').reset();

    // Buka modal
    openModal('addModal');

    // ── Ubah judul modal jadi "Edit Event" ──
    document.querySelector('#addModal .modal-title')
        .innerText = '✏️ Edit Event';

    // ── Ubah teks tombol submit ──
    document.querySelector('#addModal button[type="submit"]')
        .innerText = 'Update Event';

    // ── Ubah action form ke endpoint update ──
    document.querySelector('#addModal form')
        .action = '../process/event_update.php';

    // ── Isi semua field dengan data event ──

    // Hidden ID
    document.getElementById('id_event').value = data.id;

    // Nama event
    document.getElementById('edit_nama_event').value = data.title;

    // Lokasi
    document.getElementById('edit_location').value = data.location;

    // Organizer
    document.getElementById('edit_organizer').value = data.organizer;

    // Deskripsi
    document.getElementById('edit_description').value = data.description;

    // Kategori (select)
    document.querySelector('[name="category"]').value = data.cat;

    // Status (select)
    document.querySelector('[name="status"]').value = data.status;

    // Harga
    document.querySelector('[name="price"]').value = data.price;

    // Tags
    if (data.tags !== undefined) {
        document.querySelector('[name="tags"]').value = data.tags;
    }

    // Tanggal — konversi dari "20 Jul 2025" → "2025-07-20"
    document.querySelector('[name="event_date"]').value = convertDate(data.date);

    // Waktu
    if (data.time) {
        document.querySelector('[name="event_time"]').value = data.time;
    }

    // Preview gambar lama
    const imgWrap    = document.getElementById('currentImageWrap');
    const imgPreview = document.getElementById('currentImage');
    if (imgWrap && imgPreview) {
        if (data.gambar) {
            imgPreview.src        = '../uploads/events/' + data.gambar;
            imgWrap.style.display = 'block';
        } else {
            imgWrap.style.display = 'none';
        }
    }

    // ── Isi Ticket Categories ──
    const ticketWrapper = document.getElementById('ticketWrapper');
    ticketWrapper.innerHTML = '';

    const tickets = data.tickets && data.tickets.length > 0
        ? data.tickets
        : [{ name: '', price: '', stock: '' }]; // minimal 1 row kosong

    tickets.forEach(ticket => {
        ticketWrapper.insertAdjacentHTML('beforeend', `
        <div class="ticket-item" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <input class="form-input" type="text"   name="ticket_name[]"  placeholder="VIP"    value="${escHtml(ticket.name)}">
            <input class="form-input" type="number" name="ticket_price[]" placeholder="500000" value="${ticket.price}">
            <input class="form-input" type="number" name="ticket_stock[]" placeholder="100"    value="${ticket.stock}">
        </div>`);
    });

    // ── Isi Event Schedule ──
    const scheduleWrapper = document.getElementById('scheduleWrapper');
    scheduleWrapper.innerHTML = '';

    const schedules = data.schedules && data.schedules.length > 0
        ? data.schedules
        : [{ jam: '', kegiatan: '' }]; // minimal 1 row kosong

    schedules.forEach(sch => {
        scheduleWrapper.insertAdjacentHTML('beforeend', `
        <div class="schedule-item" style="display:grid;grid-template-columns:1fr 2fr;gap:1rem;margin-bottom:1rem;">
            <input type="text" name="schedule_jam[]"      class="form-input" placeholder="08:00"     value="${escHtml(sch.jam)}">
            <input type="text" name="schedule_kegiatan[]" class="form-input" placeholder="Open Gate" value="${escHtml(sch.kegiatan)}">
        </div>`);
    });
}

// Helper: escape HTML untuk value attribute
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/*
|--------------------------------------------------------------------------
| CONVERT DATE
|--------------------------------------------------------------------------
*/

function convertDate(dateString)
{
    const months = {
        Jan:'01',
        Feb:'02',
        Mar:'03',
        Apr:'04',
        May:'05',
        Jun:'06',
        Jul:'07',
        Aug:'08',
        Sep:'09',
        Oct:'10',
        Nov:'11',
        Dec:'12'
    };

    let parts = dateString.split(' ');

    return `${parts[2]}-${months[parts[1]]}-${parts[0]}`;
}

function resetEventForm()
{
    // Reset form
    document.querySelector('#addModal form').reset();

    // Buka modal
    openModal('addModal');

    // Judul modal → Tambah Event Baru
    document.querySelector('#addModal .modal-title')
        .innerText = '➕ Tambah Event Baru';

    // Tombol submit
    document.querySelector('#addModal button[type="submit"]')
        .innerText = 'Simpan Event';

    // Action form → store
    document.querySelector('#addModal form')
        .action = '../process/event_store.php';

    // Kosongkan hidden id
    document.getElementById('id_event').value = '';

    // Sembunyikan preview gambar lama
    const imgWrap = document.getElementById('currentImageWrap');
    if (imgWrap) imgWrap.style.display = 'none';

    // Bersihkan ticket & schedule wrapper ke 1 row kosong
    document.getElementById('ticketWrapper').innerHTML = `
    <div class="ticket-item" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
        <input class="form-input" type="text"   name="ticket_name[]"  placeholder="VIP">
        <input class="form-input" type="number" name="ticket_price[]" placeholder="500000">
        <input class="form-input" type="number" name="ticket_stock[]" placeholder="100">
    </div>`;

    document.getElementById('scheduleWrapper').innerHTML = `
    <div class="schedule-item" style="display:grid;grid-template-columns:1fr 2fr;gap:1rem;margin-bottom:1rem;">
        <input type="text" name="schedule_jam[]"       class="form-input" placeholder="08:00">
        <input type="text" name="schedule_kegiatan[]"  class="form-input" placeholder="Open Gate">
    </div>`;
}
</script>
</body>
</html>
