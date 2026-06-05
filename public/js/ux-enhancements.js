/**
 * ARTERI UX Enhancements
 *
 * Fitur tambahan tanpa dependency baru (hanya jQuery yang sudah ada):
 *  - ArteriProgress  : komponen progress indicator (determinate / indeterminate)
 *  - Drag & Drop     : zona unggah file dengan preview untuk form arsip & import
 *  - Inline Edit     : edit langsung di tabel master data
 *  - Export feedback : loading state saat tombol export diklik
 *
 * Bergantung pada helper global dari custom.js: showToast(), parseAjaxResponse(),
 * serta variabel site_url. Aman dimuat di semua halaman (tiap modul mengecek
 * keberadaan elemen targetnya sebelum aktif).
 */
(function ($) {
    'use strict';

    /* ============================================================
     * 1. ArteriProgress — progress indicator component (task 6d-4)
     * ============================================================
     * Markup mengikuti kelas di public/css/loading.css.
     *   var p = ArteriProgress.create($container);
     *   p.set(40);            // determinate 40%
     *   p.indeterminate();    // mode tak tentu (animasi berjalan)
     *   p.done();             // 100% lalu hilang
     *   p.remove();
     */
    var ArteriProgress = {
        create: function ($container, label) {
            var $wrap = $(
                '<div class="progress-wrapper">' +
                '  <div class="progress-bar-container">' +
                '    <div class="progress-bar" style="width:0%">' +
                '      <span class="progress-bar-text">0%</span>' +
                '    </div>' +
                '  </div>' +
                '  <div class="progress-label"></div>' +
                '</div>'
            );
            if (label) { $wrap.find('.progress-label').text(label); }
            $($container).append($wrap);

            var $bar  = $wrap.find('.progress-bar');
            var $text = $wrap.find('.progress-bar-text');
            var $lbl  = $wrap.find('.progress-label');

            return {
                el: $wrap,
                set: function (percent) {
                    percent = Math.max(0, Math.min(100, Math.round(percent)));
                    $bar.css('animation', '').css('width', percent + '%');
                    $text.text(percent + '%');
                    return this;
                },
                indeterminate: function () {
                    // Bar bolak-balik untuk proses yang persentasenya tak diketahui.
                    $bar.css('width', '40%');
                    $bar.css('animation', 'arteri-indeterminate 1.2s ease-in-out infinite');
                    $text.text('');
                    return this;
                },
                label: function (text) { $lbl.text(text || ''); return this; },
                done: function () {
                    this.set(100);
                    var self = this;
                    setTimeout(function () { self.el.fadeOut(400, function () { self.el.remove(); }); }, 600);
                    return this;
                },
                remove: function () { $wrap.remove(); }
            };
        }
    };
    window.ArteriProgress = ArteriProgress;

    // Keyframes untuk mode indeterminate (disuntikkan sekali).
    $('<style>').text(
        '@keyframes arteri-indeterminate {' +
        '  0% { margin-left: -40%; } 100% { margin-left: 100%; } }'
    ).appendTo('head');

    /* ============================================================
     * 2. Drag & Drop Upload (task 6b)
     * ============================================================
     * Membungkus <input type="file"> dengan zona drop + preview.
     * Otomatis aktif untuk elemen ber-atribut data-dropzone.
     */
    function humanFileSize(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024, sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + sizes[i];
    }

    function initDropzone($input) {
        if ($input.data('dz-init')) return;
        $input.data('dz-init', true);

        var accept = $input.attr('accept') || '';
        var $zone = $(
            '<div class="arteri-dropzone">' +
            '  <div class="arteri-dropzone-inner">' +
            '    <i class="glyphicon glyphicon-cloud-upload"></i>' +
            '    <p class="arteri-dropzone-text">Tarik &amp; letakkan file di sini, atau <span class="arteri-dropzone-browse">pilih file</span></p>' +
            '    <p class="arteri-dropzone-hint"></p>' +
            '  </div>' +
            '  <div class="arteri-dropzone-preview" style="display:none;"></div>' +
            '</div>'
        );

        var hint = $input.siblings('.help-block').first().text();
        if (hint) { $zone.find('.arteri-dropzone-hint').text(hint); }

        // Sisipkan zona, sembunyikan input asli (tetap fungsional).
        $input.addClass('arteri-dropzone-input').before($zone);

        function showPreview(file) {
            if (!file) {
                $zone.find('.arteri-dropzone-preview').hide().empty();
                $zone.removeClass('has-file');
                return;
            }
            $zone.addClass('has-file');
            $zone.find('.arteri-dropzone-preview').show().html(
                '<i class="glyphicon glyphicon-file"></i> ' +
                '<strong>' + $('<span>').text(file.name).html() + '</strong>' +
                ' <span class="text-muted">(' + humanFileSize(file.size) + ')</span>' +
                ' <a href="#" class="arteri-dropzone-clear" title="Hapus">&times;</a>'
            );
        }

        // Klik zona → buka dialog file.
        $zone.on('click', function (e) {
            if ($(e.target).closest('.arteri-dropzone-clear').length) return;
            $input.trigger('click');
        });

        $input.on('change', function () {
            showPreview(this.files && this.files[0]);
        });

        // Hapus file terpilih.
        $zone.on('click', '.arteri-dropzone-clear', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $input.val('');
            showPreview(null);
        });

        // Event drag & drop.
        $zone.on('dragover dragenter', function (e) {
            e.preventDefault(); e.stopPropagation();
            $zone.addClass('dragover');
        });
        $zone.on('dragleave dragend drop', function (e) {
            e.preventDefault(); e.stopPropagation();
            $zone.removeClass('dragover');
        });
        $zone.on('drop', function (e) {
            var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
            if (!files || !files.length) return;
            // Pindahkan file ke input asli agar ikut tersubmit form.
            try {
                var dt = new DataTransfer();
                dt.items.add(files[0]);
                $input[0].files = dt.files;
            } catch (err) {
                // Browser lama: fallback, input tetap kosong tapi tidak error.
            }
            showPreview(files[0]);
            $input.trigger('change');
        });
    }

    /* ============================================================
     * 3. Inline Edit untuk tabel master data (task 6c)
     * ============================================================
     * Tanpa mengubah markup view: membaca id dari link .ed* tiap baris,
     * lalu POST ke endpoint update yang sudah ada. Delegation pada
     * container supaya tetap bekerja setelah tabel di-reload via AJAX.
     */
    var INLINE_CONFIG = [
        {
            sel: '#divtabelkode', url: '/master/klas/update', idClass: 'edkode',
            // index kolom <td> -> field POST. suffix dibuang saat baca, ditambah saat render.
            cols: [
                { idx: 0, field: 'kode' },
                { idx: 1, field: 'nama' },
                { idx: 2, field: 'retensi', suffix: ' Tahun', numeric: true }
            ]
        },
        { sel: '#divtabelpenc', url: '/master/penc/update',     idClass: 'edpenc', cols: [{ idx: 1, field: 'nama' }] },
        { sel: '#divtabelpeng', url: '/master/pengolah/update', idClass: 'edpeng', cols: [{ idx: 1, field: 'nama' }] },
        { sel: '#divtabellok',  url: '/master/lokasi/update',   idClass: 'edlok',  cols: [{ idx: 1, field: 'nama' }] },
        { sel: '#divtabelmed',  url: '/master/media/update',    idClass: 'edmed',  cols: [{ idx: 1, field: 'nama' }] }
    ];

    function stripSuffix(text, suffix) {
        if (suffix && text.slice(-suffix.length) === suffix) {
            return text.slice(0, -suffix.length).trim();
        }
        return text.trim();
    }

    function initInlineEdit(cfg) {
        var $container = $(cfg.sel);
        if (!$container.length) return;

        // Tandai sel yang bisa diedit (untuk styling & petunjuk) tiap render.
        function markCells() {
            $container.find('tbody tr, tr').each(function () {
                var $tds = $(this).children('td');
                if (!$tds.length) return;
                cfg.cols.forEach(function (c) {
                    var $td = $tds.eq(c.idx);
                    if ($td.length && !$td.hasClass('ie-cell')) {
                        $td.addClass('ie-cell').attr('title', 'Klik dua kali untuk edit');
                    }
                });
            });
        }
        markCells();
        // Re-mark setelah reload AJAX mengganti isi container.
        var observer = new MutationObserver(markCells);
        observer.observe($container[0], { childList: true, subtree: true });

        $container.on('dblclick', 'td.ie-cell', function () {
            var $td = $(this);
            if ($td.hasClass('ie-editing')) return;

            var $row    = $td.closest('tr');
            var colIdx  = $row.children('td').index($td);
            var colCfg  = cfg.cols.filter(function (c) { return c.idx === colIdx; })[0];
            if (!colCfg) return;

            var id = $row.find('.' + cfg.idClass).attr('id');
            if (!id) return;

            var current = stripSuffix($td.text(), colCfg.suffix);
            $td.addClass('ie-editing');
            var $input = $('<input type="text" class="form-control input-sm ie-input">').val(current);
            $td.empty().append($input);
            $input.focus().select();

            var done = false;
            function finish(save) {
                if (done) return; done = true;
                var newVal = $.trim($input.val());

                if (!save || newVal === current) {
                    renderCell($td, current, colCfg);
                    return;
                }
                if (newVal === '') {
                    showToast('Nilai tidak boleh kosong.', 'error');
                    renderCell($td, current, colCfg);
                    return;
                }
                if (colCfg.numeric && !/^\d+$/.test(newVal)) {
                    showToast('Nilai harus berupa angka.', 'error');
                    renderCell($td, current, colCfg);
                    return;
                }
                saveRow($td, $row, id, colCfg, newVal, current);
            }

            $input.on('keydown', function (e) {
                if (e.which === 13) { e.preventDefault(); finish(true); }
                else if (e.which === 27) { finish(false); }
            });
            $input.on('blur', function () { finish(true); });
        });

        function renderCell($td, value, colCfg) {
            $td.removeClass('ie-editing').empty()
               .text(value + (colCfg.suffix || ''));
        }

        function saveRow($td, $row, id, colCfg, newVal, oldVal) {
            // Kumpulkan SEMUA field konfigurasi dari baris (endpoint update butuh lengkap).
            var $tds = $row.children('td');
            var payload = { id: id };
            cfg.cols.forEach(function (c) {
                if (c.idx === colCfg.idx) {
                    payload[c.field] = newVal;
                } else {
                    payload[c.field] = stripSuffix($tds.eq(c.idx).text(), c.suffix);
                }
            });

            var $spinner = $('<span class="inline-loading"></span>');
            $td.empty().append($('<span>').text(newVal)).append($spinner);

            $.post(site_url + colCfg.url, payload)
                .done(function (data) {
                    var resp = parseAjaxResponse(data);
                    if (resp.status === 'success') {
                        renderCell($td, newVal, colCfg);
                        $td.addClass('fade-in');
                        showToast('Data berhasil diperbarui.');
                    } else {
                        renderCell($td, oldVal, colCfg);
                        var msg = resp.message;
                        if (!msg && resp.errors) { msg = Object.keys(resp.errors).map(function (k) { return resp.errors[k]; }).join(' '); }
                        showToast(msg || 'Gagal memperbarui data.', 'error');
                    }
                })
                .fail(function () {
                    renderCell($td, oldVal, colCfg);
                    // ajaxError global juga menampilkan toast; ini jaga-jaga.
                });
        }
    }

    /* ============================================================
     * 4. Export feedback (task 6d-6)
     * ============================================================
     * Tombol export adalah link GET (download). Tampilkan loading state
     * singkat & progress indeterminate saat diklik, lalu pulihkan.
     */
    function initExportFeedback() {
        $(document).on('click', '.js-export', function () {
            var $btn = $(this);
            if ($btn.hasClass('btn-loading')) return;
            var original = $btn.html();
            $btn.addClass('btn-loading').css('min-width', $btn.outerWidth() + 'px').html('Menyiapkan file...');
            showToast('Menyiapkan file untuk diunduh...');
            // Download tidak memicu event JS; pulihkan setelah jeda.
            setTimeout(function () {
                $btn.removeClass('btn-loading').html(original);
            }, 3500);
        });
    }

    /* ============================================================
     * Bootstrap semua modul
     * ============================================================ */
    $(function () {
        // Drag & drop untuk semua input ber-atribut data-dropzone.
        $('input[type="file"][data-dropzone]').each(function () {
            initDropzone($(this));
        });

        // Inline edit master data.
        INLINE_CONFIG.forEach(initInlineEdit);

        // Loading state tombol export.
        initExportFeedback();
    });

})(jQuery);
