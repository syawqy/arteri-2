/**
 * This application is licensed under GNU General Public License version 3
 * Developers:
 * Syauqi Fuadi ( xfuadi@gmail.com )
 * Arie Nugraha ( dicarve@gmail.com )
 *
 */

$(document).ready(function() {
	var url = $(location).attr("href");
	var segments = url.split("/");

	// CSRF protection: inject token into all AJAX requests
	// Reads from meta tag rendered by header.php (data-name/data-value attributes)
	var csrfMeta = $('meta[data-name][data-value]');
	var csrfName = csrfMeta.length ? csrfMeta.data('name') : '';
	var csrfHash = csrfMeta.length ? csrfMeta.data('value') : '';
	$(document).ajaxSend(function(e, xhr, settings) {

		if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && settings.type !== undefined) {
			if (settings.data && typeof settings.data === 'string') {
				if (settings.data.indexOf(csrfName + '=') === -1) {
					settings.data += '&' + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash);
				}
			} else if (settings.data && typeof settings.data === 'object') {
				settings.data[csrfName] = csrfHash;
			}
		}
	});

	// Global AJAX error handler
	$(document).ajaxError(function(e, xhr, settings, error) {
		if (xhr.status === 403) {
			alert('Sesi telah berakhir. Silakan login kembali.');
			window.location.href = site_url + '/login';
		}
	});

	// Toast notification helper (replaces alert)
	window.showToast = function(message, type) {
		type = type || 'success';
		var bg = type === 'error' ? '#d9534f' : '#5cb85c';
		var $toast = $('<div class="arteri-toast">' + message + '</div>');
		$toast.css({
			position: 'fixed', top: '70px', right: '20px', zIndex: 99999,
			background: bg, color: '#fff', padding: '12px 20px', borderRadius: '4px',
			boxShadow: '0 2px 8px rgba(0,0,0,0.2)', fontSize: '14px', display: 'none'
		});
		$('body').append($toast);
		$toast.fadeIn(300).delay(3000).fadeOut(500, function() { $(this).remove(); });
	};

	// Parse JSON response helper
	window.parseAjaxResponse = function(responseText) {
		try { return typeof responseText === 'string' ? JSON.parse(responseText) : responseText; }
		catch(e) { return { status: 'error', message: 'Respons server tidak valid.' }; }
	};

	$.each($('form[data-ajax="true"]'), function() {
		/** handled inline */;
	});

	$.ajaxSetup({
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		}
	});

	$("#tanggal").datepicker({
		maxDate: "0",
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd"
	});
	$("#tgl_pinjam").datepicker({
		maxDate: "0",
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd"
	});
	$("#tgl_haruskembali").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd"
	});

	/** Fungsi untuk menghapus data arsip */
	$(".deldata").click(function() {
		var d = $(this).attr("id");
		$("#deliddata").val(d);
	});
	$("#deldatago").on("click", function() {
		$("#fdeldata").submit();
	});
	$("#fdeldata").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil dihapus'); $("#deldata").modal("hide"); window.location.reload(true); }
		else { showToast(resp.message || 'Gagal menghapus data', 'error'); }
	}});

	/** Fungsi untuk menghapus data sirkulasi arsip */
	$(".sdeldata").click(function() {
		var d = $(this).attr("id");
		$("#deliddata").val(d);
	});
	$("#sdeldatago").on("click", function() {
		$("#fsdeldata").submit();
	});
	$("#fsdeldata").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil dihapus'); $("#deldata").modal("hide"); window.location.reload(true); }
		else { showToast(resp.message || 'Gagal menghapus data', 'error'); }
	}});

	/** Fungsi untuk mengembalikan arsip dalam sirkulasi */
	$(".kemdata").click(function() {
		var d = $(this).attr("id");
		$("#kemid").val(d);
	});
	$("#kemarsipgo").on("click", function() {
		$("#fkemarsip").submit();
	});
	$("#fkemarsip").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Arsip berhasil dikembalikan'); $("#arsipkembali").modal("hide"); window.location.reload(true); }
		else { showToast(resp.message || 'Gagal mengembalikan arsip', 'error'); }
	}});

	/** Fungsi untuk menghapus file attachment arsip */
	$("#delfilego").on("click", function() {
		$("#fdelfile").submit();
	});
	$("#fdelfile").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'File berhasil dihapus'); $("#uplodfile").show(); $("#linkfile").hide(); $("#delfile").modal("hide"); }
		else { showToast(resp.message || 'Gagal menghapus file', 'error'); }
	}});

	/** Fungsi-fungsi terkait dengan data master user aplikasi arsip */
	function reloaduser() {
		$.ajax({
			type: "GET",
			url: site_url + "/user/reload",
			success: function(html) {
				$("#divtabeluser").html(html);
			}
		});
	}
	$("#divtabeluser").on("click", ".deluser", function() {
		var d = $(this).attr("id");
		$("#deliduser").val(d);
	});
	$("#delusergo").on("click", function() {
		$("#fdeluser").submit();
	});
	$("#fdeluser").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'User berhasil dihapus'); reloaduser(); $("#deluser").modal("hide"); }
		else { showToast(resp.message || 'Gagal menghapus user', 'error'); }
	}});
	$("#editusergo").on("click", function() {
		$("#feduser").submit();
	});
	$("#feduser").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'User berhasil diperbarui'); reloaduser(); $("#feduser")[0].reset(); $("#edituser").modal("hide"); }
		else { showToast(resp.message || 'Gagal memperbarui user', 'error'); }
	}});

	$("#addusergo").on("click", function() {
		var d = $("#username").val();
		$.ajax({
			type: "POST",
			url: site_url + "/user/cekUsername",
			data: "username=" + d,
			cache: false,
			success: function(ahtml) {
				var html = parseAjaxResponse(ahtml);
				if (html.msg == "ok") {
					$("#fadduser").submit();
				} else {
					showToast(html.message || "Username sudah terpakai!", "error");
				}
			}
		});
	});
	$("#fadduser").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'User berhasil dibuat'); reloaduser(); $("#adduser").modal("hide"); $("#password, #conf_password").removeClass("input-error"); $("#fadduser")[0].reset(); }
		else { showToast(resp.message || 'Gagal membuat user', 'error'); if (resp.errors) { $.each(resp.errors, function(k, v) { $("#" + k + ", [name=" + k + "]").addClass("input-error"); }); } }
	}});

	$("#divtabeluser").on("click", ".eduser", function() {
		var d = $(this).attr("id");
		$.ajax({
			type: "POST",
			url: site_url + "/user/get",
			data: "id=" + d,
			cache: false,
			success: function(ahtml) {
				var html = parseAjaxResponse(ahtml);
				if (!html || html.status === 'error') return;
				$("#feduser")[0].reset();
				$("#eusername").val(html.username);
				$("#etipe").val(html.tipe);
				$("#eakses_klas").val(html.akses_klas);
				$("#ediduser").val(html.id);
				if (html.akses_modul) {
					var akses_modul = typeof html.akses_modul === 'string' ? JSON.parse(html.akses_modul) : html.akses_modul;
					if (typeof akses_modul == "object") {
						if (akses_modul.entridata == "on") $("#emodul1").prop("checked", true);
						if (akses_modul.sirkulasi == "on") $("#emodul2").prop("checked", true);
						if (akses_modul.klasifikasi == "on") $("#emodul3").prop("checked", true);
						if (akses_modul.pencipta == "on") $("#emodul4").prop("checked", true);
						if (akses_modul.pengolah == "on") $("#emodul5").prop("checked", true);
						if (akses_modul.lokasi == "on") $("#emodul6").prop("checked", true);
						if (akses_modul.media == "on") $("#emodul7").prop("checked", true);
						if (akses_modul.user == "on") $("#emodul8").prop("checked", true);
						if (akses_modul.import == "on") $("#emodul9").prop("checked", true);
					}
				}
			}
		});
	});

	////////////////////// MASTER KODE
	function reloadkode() {
		$.ajax({
			type: "GET",
			url: site_url + "/master/klas/reload",
			cache: false,
			success: function(html) { $("#divtabelkode").html(html); }
		});
	}
	$("#divtabelkode").on("click", ".delkode", function() {
		var d = $(this).attr("id");
		$("#delidkode").val(d);
	});
	$("#delkodego").on("click", function() { $("#fdelkode").submit(); });
	$("#fdelkode").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil dihapus'); reloadkode(); $("#delkode").modal("hide"); }
		else { showToast(resp.message || 'Gagal menghapus', 'error'); }
	}});
	$("#editkodego").on("click", function() { $("#fedkode").submit(); });
	$("#fedkode").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); reloadkode(); $("#editkode").modal("hide"); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#addkodego").on("click", function() { $("#faddkode").submit(); });
	$("#faddkode").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); reloadkode(); $("#addkode").modal("hide"); $("#faddkode")[0].reset(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#divtabelkode").on("click", ".edkode", function() {
		var d = $(this).attr("id");
		$.ajax({
			type: "POST",
			url: site_url + "/master/klas/get",
			data: "id=" + d,
			cache: false,
			success: function(ahtml) {
				var html = parseAjaxResponse(ahtml);
				if (!html || html.status === 'error') return;
				$("#ekode").val(html.kode);
				$("#enama").val(html.nama);
				$("#eretensi").val(html.retensi);
				$("#edidkode").val(html.id);
			}
		});
	});

	/** MASTER PENCIPTA */
	function reloadpenc() {
		$.ajax({
			type: "GET",
			url: site_url + "/master/penc/reload",
			cache: false,
			success: function(html) { $("#divtabelpenc").html(html); }
		});
	}
	$("#divtabelpenc").on("click", ".delpenc", function() {
		var d = $(this).attr("id");
		$("#delidpenc").val(d);
	});
	$("#delpencgo").on("click", function() { $("#fdelpenc").submit(); });
	$("#fdelpenc").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil dihapus'); $("#delpenc").modal("hide"); reloadpenc(); }
		else { showToast(resp.message || 'Gagal menghapus', 'error'); }
	}});
	$("#editpencgo").on("click", function() { $("#fedpenc").submit(); });
	$("#fedpenc").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#editpenc").modal("hide"); reloadpenc(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#addpencgo").on("click", function() {
		var form = $("#faddpenc");
		$.post(form.attr("action"), form.serialize()).done(function(data) {
			var resp = parseAjaxResponse(data);
			if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#addpenc").modal("hide"); $("#faddpenc")[0].reset(); reloadpenc(); }
			else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
		});
	});
	$("#divtabelpenc").on("click", ".edpenc", function() {
		var d = $(this).attr("id");
		$.ajax({
			type: "POST", url: site_url + "/master/penc/get", data: "id=" + d, cache: false,
			success: function(ahtml) {
				var html = parseAjaxResponse(ahtml);
				if (!html || html.status === 'error') return;
				$("#enama").val(html.nama_pencipta);
				$("#edidpenc").val(html.id);
			}
		});
	});

	/** MASTER PENGOLAH */
	function reloadpeng() {
		$.ajax({
			type: "GET", url: site_url + "/master/pengolah/reload", cache: false,
			success: function(html) { $("#divtabelpeng").html(html); }
		});
	}
	$("#divtabelpeng").on("click", ".delpeng", function() { var d = $(this).attr("id"); $("#delidpeng").val(d); });
	$("#delpenggo").on("click", function() { $("#fdelpeng").submit(); });
	$("#fdelpeng").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil dihapus'); $("#delpeng").modal("hide"); reloadpeng(); }
		else { showToast(resp.message || 'Gagal menghapus', 'error'); }
	}});
	$("#editpenggo").on("click", function() { $("#fedpeng").submit(); });
	$("#fedpeng").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#editpeng").modal("hide"); reloadpeng(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#addpenggo").on("click", function() { $("#faddpeng").submit(); });
	$("#faddpeng").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#addpeng").modal("hide"); $("#faddpeng")[0].reset(); reloadpeng(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#divtabelpeng").on("click", ".edpeng", function() {
		var d = $(this).attr("id");
		$.ajax({
			type: "POST", url: site_url + "/master/pengolah/get", data: "id=" + d, cache: false,
			success: function(ahtml) {
				var html = parseAjaxResponse(ahtml);
				if (!html || html.status === 'error') return;
				$("#enama").val(html.nama_pengolah);
				$("#edidpeng").val(html.id);
			}
		});
	});

	/** MASTER LOKASI */
	function reloadlok() {
		$.ajax({
			type: "GET", url: site_url + "/master/lokasi/reload", cache: false,
			success: function(html) { $("#divtabellok").html(html); }
		});
	}
	$("#divtabellok").on("click", ".dellok", function() { var d = $(this).attr("id"); $("#delidlok").val(d); });
	$("#dellokgo").on("click", function() { $("#fdellok").submit(); });
	$("#fdellok").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil dihapus'); $("#dellok").modal("hide"); reloadlok(); }
		else { showToast(resp.message || 'Gagal menghapus', 'error'); }
	}});
	$("#editlokgo").on("click", function() { $("#fedlok").submit(); });
	$("#fedlok").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#editlok").modal("hide"); reloadlok(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#addlokgo").on("click", function() { $("#faddlok").submit(); });
	$("#faddlok").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#addlok").modal("hide"); $("#faddlok")[0].reset(); reloadlok(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#divtabellok").on("click", ".edlok", function() {
		var d = $(this).attr("id");
		$.ajax({
			type: "POST", url: site_url + "/master/lokasi/get", data: "id=" + d, cache: false,
			success: function(ahtml) {
				var html = parseAjaxResponse(ahtml);
				if (!html || html.status === 'error') return;
				$("#enama").val(html.nama_lokasi);
				$("#edidlok").val(html.id);
			}
		});
	});

	/** MASTER MEDIA */
	function reloadmed() {
		$.ajax({
			type: "GET", url: site_url + "/master/media/reload", cache: false,
			success: function(html) { $("#divtabelmed").html(html); }
		});
	}
	$("#divtabelmed").on("click", ".delmed", function() { var d = $(this).attr("id"); $("#delidmed").val(d); });
	$("#delmedgo").on("click", function() { $("#fdelmed").submit(); });
	$("#fdelmed").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil dihapus'); $("#delmed").modal("hide"); reloadmed(); }
		else { showToast(resp.message || 'Gagal menghapus', 'error'); }
	}});
	$("#editmedgo").on("click", function() { $("#fedmed").submit(); });
	$("#fedmed").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#editmed").modal("hide"); reloadmed(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#addmedgo").on("click", function() { $("#faddmed").submit(); });
	$("#faddmed").ajaxForm({ success: function(o) {
		var resp = parseAjaxResponse(o);
		if (resp.status === 'success') { showToast(resp.message || 'Data berhasil disimpan'); $("#addmed").modal("hide"); $("#faddmed")[0].reset(); reloadmed(); }
		else { showToast(resp.message || 'Gagal menyimpan', 'error'); }
	}});
	$("#divtabelmed").on("click", ".edmed", function() {
		var d = $(this).attr("id");
		$.ajax({
			type: "POST", url: site_url + "/master/media/get", data: "id=" + d, cache: false,
			success: function(ahtml) {
				var html = parseAjaxResponse(ahtml);
				if (!html || html.status === 'error') return;
				$("#enama").val(html.nama_media);
				$("#edidmed").val(html.id);
			}
		});
	});

	/** XHR/Autocomplete untuk sirkulasi */
	$(".xhr").each(function() {
		var $this = $(this);
		$this.autoComplete({
			source: function(term, response) {
				$.getJSON($this.data("xhr") + "/" + term, function(data) {
					response(data);
				});
			}
		});
	});
});
