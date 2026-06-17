<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
    margin-top: 20px;
}
.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    left: 11px;
    height: 100%;
    width: 2px;
    background: #e0e0e0;
}
.timeline-item {
    position: relative;
    margin-bottom: 25px;
}
.timeline-marker {
    position: absolute;
    top: 5px;
    left: -24px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #0d6efd;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #0d6efd;
}
.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.developer-support-box {
    background-color: #12141d;
    color: #e2e2e2;
    padding: 1.5rem;
    border-radius: 8px;
}
.developer-support-box .alert-custom {
    background-color: #2a1618;
    border-left: 4px solid #ef4444;
    padding: 1rem;
    border-radius: 4px;
    color: #e2e2e2;
}
.developer-support-box .alert-custom strong {
    color: #fc8181;
}
.btn-saweria {
    background-color: #d99a22;
    color: #fff;
    font-weight: bold;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(217, 154, 34, 0.4);
    transition: all 0.3s ease;
}
.btn-saweria:hover {
    background-color: #f5b027;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 0 20px rgba(217, 154, 34, 0.6);
}
</style>

<!-- Modal -->
<div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="changelogModalLabel"><i class="fas fa-history text-primary me-2"></i> Version History & Support</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <!-- Dev Support Box -->
        <div class="developer-support-box shadow-sm mb-4">
            <p class="mb-3">Aplikasi ini dikembangkan secara mandiri sebagai inisiatif pribadi demi efisiensi layanan medis.</p>
            <div class="alert-custom mb-3">
                Proyek ini berjalan murni atas dedikasi pengembang <strong class="text-danger">tanpa dukungan pendanaan atau kompensasi khusus dari pihak manajemen.</strong>
            </div>
            <p class="mb-4 text-muted" style="font-size: 0.95rem;">Dukungan sukarela Anda sangat berarti untuk memastikan sistem ini tetap terawat dan berfungsi optimal.</p>
            <div class="text-center pb-2">
                <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-saweria px-4 py-2">
                    <i class="fas fa-search-dollar me-1"></i> Dukung Developer (Saweria)
                </a>
            </div>
            <div class="text-center mt-4">
                <span class="text-primary font-monospace" style="font-size: 0.85rem;">-- Mochammad Ichsan Maulana --</span>
            </div>
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3">Release Notes</h6>
        <div id="changelogContent" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted small">Memuat log pembaruan...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var changelogModal = document.getElementById('changelogModal');
    if(changelogModal) {
        changelogModal.addEventListener('show.bs.modal', function () {
            var contentDiv = document.getElementById('changelogContent');
            if(contentDiv.getAttribute('data-loaded') === 'true') return;

            fetch('change_log_berkas_digital_klaim.md')
            .then(response => {
                if(!response.ok) throw new Error('File log tidak ditemukan');
                return response.text();
            })
            .then(text => {
                let lines = text.split('\n');
                let entries = [];
                let currentEntry = null;

                lines.forEach(line => {
                    line = line.trim();
                    if(!line) return;
                    
                    if (line.startsWith('## [')) {
                        if(currentEntry) entries.push(currentEntry);
                        currentEntry = { title: line.replace('##', '').trim(), body: [] };
                    } else if (currentEntry) {
                        currentEntry.body.push(line);
                    }
                });
                if(currentEntry) entries.push(currentEntry);

                // Reverse untuk order terbaru di atas
                entries.reverse();

                let html = '<div class="timeline text-start">';
                entries.forEach(entry => {
                    html += '<div class="timeline-item"><div class="timeline-marker"></div><div class="timeline-content">';
                    html += '<h6 class="fw-bold text-primary mb-2">' + entry.title + '</h6>';
                    
                    entry.body.forEach(bline => {
                        if (bline.startsWith('### ')) {
                            html += '<div class="fw-bold text-dark mt-2 mb-1" style="font-size:0.85rem;"><i class="fas fa-tags text-secondary me-1"></i> ' + bline.replace('###', '').trim() + '</div>';
                        } else if (bline.startsWith('- ')) {
                            let li = bline.replace('- ', '').trim();
                            li = li.replace(/\*\*(.*?)\*\*/g, '<strong class="text-dark">$1</strong>');
                            html += '<div class="mb-1 text-secondary ms-2" style="font-size:0.85rem;"><i class="fas fa-caret-right text-muted me-1"></i> ' + li + '</div>';
                        }
                    });
                    
                    html += '</div></div>';
                });
                html += '</div>';
                contentDiv.innerHTML = html;
                contentDiv.setAttribute('data-loaded', 'true');
            })
            .catch(error => {
                contentDiv.innerHTML = '<div class="alert alert-danger">Gagal memuat history pembaruan. ('+error.message+')</div>';
            });
        });
    }
});
</script>
