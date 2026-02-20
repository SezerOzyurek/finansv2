<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- Tabler Icons (webfont). Pinned to a version; file lives under /dist. -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.36.1/dist/tabler-icons.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  /* Prevent accidental horizontal scrolling on mobile while keeping intentional inner scroll areas. */
  html, body { overflow-x: hidden; }
  body { margin: 0; }

  /* Simple lightbox + uploader polish (no external libs). */
  .ux-lightbox.hidden { display: none; }
  .ux-lightbox { position: fixed; inset: 0; z-index: 9999; }
  .ux-lightbox__backdrop { position: absolute; inset: 0; background: rgba(2,6,23,.78); backdrop-filter: blur(2px); }
  .ux-lightbox__panel { position: relative; height: 100%; width: 100%; display: grid; place-items: center; padding: 18px; }
  .ux-lightbox__img { max-height: calc(100vh - 120px); max-width: min(1100px, 92vw); border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,.45); background: #0b1220; }
  .ux-lightbox__bar { position: absolute; top: 14px; right: 14px; display: flex; gap: 8px; }
  .ux-lightbox__btn { background: rgba(255,255,255,.10); color: #fff; border: 1px solid rgba(255,255,255,.18); border-radius: 14px; padding: 8px 10px; font-weight: 700; }
  .ux-lightbox__btn:hover { background: rgba(255,255,255,.16); }

  .ux-upload-overlay.hidden { display: none; }
  .ux-upload-overlay { position: fixed; inset: 0; z-index: 9998; display: grid; place-items: center; background: rgba(2,6,23,.55); backdrop-filter: blur(2px); }
  .ux-upload-overlay__card { width: min(520px, calc(100vw - 2rem)); background: rgba(255,255,255,.92); border: 1px solid rgba(226,232,240,.9); border-radius: 22px; padding: 18px; box-shadow: 0 30px 90px rgba(0,0,0,.25); }
  .ux-upload-overlay__title { font-weight: 900; letter-spacing: -0.02em; color: rgb(15 23 42); }
  .ux-upload-overlay__sub { margin-top: 6px; font-size: 13px; color: rgb(71 85 105); }
  .ux-spin { width: 18px; height: 18px; border: 2px solid rgb(148 163 184); border-top-color: rgb(15 23 42); border-radius: 999px; display: inline-block; animation: uxspin .9s linear infinite; }
  @keyframes uxspin { to { transform: rotate(360deg); } }

  /* Toastr theme */
  #toast-container > div {
    border-radius: 18px !important;
    padding: 14px 14px 14px 16px !important;
    box-shadow: 0 22px 60px rgba(2, 6, 23, .28) !important;
    opacity: 1 !important;
  }
  #toast-container > .toast {
    background: rgba(15, 23, 42, .92) !important;
    border: 1px solid rgba(148, 163, 184, .28) !important;
    color: #fff !important;
    backdrop-filter: blur(10px);
  }
  #toast-container > .toast-success { border-color: rgba(16, 185, 129, .40) !important; }
  #toast-container > .toast-error { border-color: rgba(244, 63, 94, .45) !important; }
  #toast-container > .toast-warning { border-color: rgba(245, 158, 11, .50) !important; }
  #toast-container > .toast-info { border-color: rgba(56, 189, 248, .45) !important; }
  #toast-container .toast-title { font-weight: 900; letter-spacing: -0.01em; }
  #toast-container .toast-message { font-weight: 600; }
  #toast-container .toast-progress { height: 3px !important; opacity: .85; background: rgba(255, 255, 255, .55) !important; }
  #toast-container .toast-close-button { color: rgba(255,255,255,.88) !important; text-shadow: none !important; opacity: .85 !important; }
  #toast-container .toast-close-button:hover { opacity: 1 !important; }

  /* SweetAlert2 theme */
  .swal2-container { padding: 14px !important; }
  .swal2-popup {
    border-radius: 26px !important;
    border: 1px solid rgba(226, 232, 240, .85) !important;
    box-shadow: 0 28px 90px rgba(2, 6, 23, .30) !important;
  }
  .swal2-title { font-weight: 900 !important; letter-spacing: -0.02em; color: rgb(15 23 42) !important; }
  .swal2-html-container { color: rgb(71 85 105) !important; }
  .swal2-actions { gap: 10px !important; margin-top: 16px !important; }
  .swal2-styled { border-radius: 16px !important; font-weight: 900 !important; padding: 10px 14px !important; box-shadow: none !important; }
  .swal2-styled:focus { box-shadow: 0 0 0 4px rgba(2,132,199,.18) !important; }
</style>

<script>
(function () {
  // Lightbox (click-to-zoom images).
  function ensureLightbox() {
    if (document.getElementById("uxLightbox")) return;
    var lb = document.createElement("div");
    lb.id = "uxLightbox";
    lb.className = "ux-lightbox hidden";
    lb.innerHTML =
      '<div class="ux-lightbox__backdrop" data-uxlb-close></div>' +
      '<div class="ux-lightbox__panel" role="dialog" aria-modal="true">' +
      '  <div class="ux-lightbox__bar">' +
      '    <a class="ux-lightbox__btn" id="uxLightboxOpen" href="#" target="_blank" rel="noreferrer">Aç</a>' +
      '    <button class="ux-lightbox__btn" type="button" data-uxlb-close>Kapat</button>' +
      '  </div>' +
      '  <img class="ux-lightbox__img" id="uxLightboxImg" alt="">' +
      '</div>';
    document.body.appendChild(lb);
  }
  function openLightbox(src) {
    ensureLightbox();
    var lb = document.getElementById("uxLightbox");
    var img = document.getElementById("uxLightboxImg");
    var a = document.getElementById("uxLightboxOpen");
    if (!lb || !img || !a) return;
    img.src = src;
    a.href = src;
    lb.classList.remove("hidden");
  }
  function closeLightbox() {
    var lb = document.getElementById("uxLightbox");
    var img = document.getElementById("uxLightboxImg");
    if (!lb) return;
    lb.classList.add("hidden");
    if (img) img.src = "";
  }

  // Global upload overlay for "yükleniyor" feedback.
  function ensureUploadOverlay() {
    if (document.getElementById("uxUploadOverlay")) return;
    var ov = document.createElement("div");
    ov.id = "uxUploadOverlay";
    ov.className = "ux-upload-overlay hidden";
    ov.innerHTML =
      '<div class="ux-upload-overlay__card">' +
      '  <div class="flex items-center gap-3">' +
      '    <span class="ux-spin"></span>' +
      '    <div>' +
      '      <div class="ux-upload-overlay__title" id="uxUploadTitle">Yükleniyor...</div>' +
      '      <div class="ux-upload-overlay__sub" id="uxUploadSub">Lütfen bekleyin.</div>' +
      '    </div>' +
      '  </div>' +
      '</div>';
    document.body.appendChild(ov);
  }
  window.uxShowUploading = function (title, sub) {
    ensureUploadOverlay();
    var ov = document.getElementById("uxUploadOverlay");
    var t = document.getElementById("uxUploadTitle");
    var s = document.getElementById("uxUploadSub");
    if (t) t.textContent = title || "Yükleniyor...";
    if (s) s.textContent = sub || "Lütfen bekleyin.";
    if (ov) ov.classList.remove("hidden");
  };
  window.uxHideUploading = function () {
    var ov = document.getElementById("uxUploadOverlay");
    if (ov) ov.classList.add("hidden");
  };

  // Photo uploader previews for any <input type="file" data-photo-input>.
  function rebuildFileList(input, keepFiles) {
    try {
      var dt = new DataTransfer();
      (keepFiles || []).forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
    } catch (e) {
      // If DataTransfer is not supported, we can't remove individual files cleanly.
    }
  }
  function renderPreviews(input) {
    var wrap = input.closest("[data-photo-uploader]");
    if (!wrap) return;
    var grid = wrap.querySelector("[data-photo-previews]");
    var hint = wrap.querySelector("[data-photo-hint]");
    if (!grid) return;

    var files = Array.prototype.slice.call(input.files || []);
    if (files.length > 5) {
      files = files.slice(0, 5);
      rebuildFileList(input, files);
    }
    if (hint) hint.textContent = files.length ? (files.length + " dosya seçildi") : "Dosya seçilmedi";

    grid.innerHTML = "";
    files.forEach(function (f, idx) {
      var url = "";
      try { url = URL.createObjectURL(f); } catch (e) {}

      var name = (f && f.name) ? String(f.name) : "";
      var ext = "";
      if (name && name.indexOf(".") !== -1) ext = name.split(".").pop().toLowerCase();
      var mime = (f && f.type) ? String(f.type).toLowerCase() : "";
      var isImage = (mime.indexOf("image/") === 0) || (["jpg","jpeg","png","webp","gif","bmp","svg"].indexOf(ext) !== -1);
      var isPdf = (mime === "application/pdf") || (ext === "pdf");

      var card = document.createElement("div");
      card.className = "relative overflow-hidden rounded-2xl border border-slate-200 bg-white";

      if (isImage) {
        card.innerHTML =
          '<button type="button" class="absolute right-2 top-2 rounded-xl bg-rose-600 px-2 py-1 text-xs font-semibold text-white" data-photo-remove="'+idx+'">Sil</button>' +
          '<button type="button" class="block w-full" data-lightbox-src="'+(url || "")+'">' +
          '  <img src="'+(url || "")+'" class="h-24 w-full object-cover" alt="">' +
          '</button>' +
          '<div class="px-3 py-2 text-[11px] text-slate-600 truncate" title="'+(name || "")+'">'+(name || "foto")+'</div>';
      } else {
        var label = isPdf ? "PDF" : "Dosya";
        var icon = isPdf ? "ti ti-file-type-pdf text-rose-600" : "ti ti-file text-slate-600";
        card.innerHTML =
          '<button type="button" class="absolute right-2 top-2 rounded-xl bg-rose-600 px-2 py-1 text-xs font-semibold text-white" data-photo-remove="'+idx+'">Sil</button>' +
          '<button type="button" class="flex h-24 w-full items-center justify-center gap-2 bg-slate-50 text-sm font-extrabold text-slate-700" data-file-open="'+(url || "")+'">' +
          '  <i class="'+icon+'"></i>'+label +
          '</button>' +
          '<div class="px-3 py-2 text-[11px] text-slate-600 truncate" title="'+(name || "")+'">'+(name || "dosya")+'</div>';
      }

      grid.appendChild(card);
    });
  }

  document.addEventListener("change", function (e) {
    var input = e.target;
    if (!input || input.tagName !== "INPUT") return;
    if (input.type !== "file") return;
    if (!input.hasAttribute("data-photo-input")) return;
    renderPreviews(input);
  });

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-lightbox-src]");
    if (btn) {
      e.preventDefault();
      var src = btn.getAttribute("data-lightbox-src");
      if (src) openLightbox(src);
      return;
    }
    var fileBtn = e.target.closest("[data-file-open]");
    if (fileBtn) {
      e.preventDefault();
      var href = fileBtn.getAttribute("data-file-open");
      if (href) window.open(href, "_blank", "noreferrer");
      return;
    }
    var rem = e.target.closest("[data-photo-remove]");
    if (rem) {
      e.preventDefault();
      var idx = parseInt(rem.getAttribute("data-photo-remove") || "0", 10);
      var wrap = rem.closest("[data-photo-uploader]");
      if (!wrap) return;
      var input = wrap.querySelector("input[type=file][data-photo-input]");
      if (!input) return;
      var files = Array.prototype.slice.call(input.files || []);
      files.splice(idx, 1);
      rebuildFileList(input, files);
      renderPreviews(input);
      return;
    }
    if (e.target && e.target.closest && e.target.closest("[data-uxlb-close]")) {
      e.preventDefault();
      closeLightbox();
      return;
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeLightbox();
  });

  // Mobile sidebar toggle (Tailwind layout).
  function openSidebar() {
    var sb = document.getElementById("sidebar");
    var ov = document.getElementById("sidebarOverlay");
    if (!sb || !ov) return;
    sb.classList.remove("-translate-x-full");
    ov.classList.remove("hidden");
  }
  function closeSidebar() {
    var sb = document.getElementById("sidebar");
    var ov = document.getElementById("sidebarOverlay");
    if (!sb || !ov) return;
    sb.classList.add("-translate-x-full");
    ov.classList.add("hidden");
  }

  document.addEventListener("click", function (e) {
    var openBtn = e.target.closest("[data-sidebar-open]");
    var closeBtn = e.target.closest("[data-sidebar-close]");
    if (openBtn) { e.preventDefault(); openSidebar(); return; }
    if (closeBtn) { e.preventDefault(); closeSidebar(); return; }

    var ov = document.getElementById("sidebarOverlay");
    if (ov && e.target === ov) closeSidebar();
  });

  // Keep existing behaviors (visibility toggles + date filter) intact.
  $(document).ready(function () {
    // Global Toastr defaults (theme is CSS-driven above).
    if (window.toastr) {
      toastr.options = Object.assign({}, toastr.options, {
        closeButton: true,
        newestOnTop: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3200,
        extendedTimeOut: 1600,
        showDuration: 140,
        hideDuration: 120,
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
        preventDuplicates: true
      });
    }

    // Global SweetAlert2 defaults.
    if (window.Swal && typeof window.Swal.mixin === "function") {
      window.Swal = window.Swal.mixin({
        confirmButtonColor: "#0f172a",
        cancelButtonColor: "#64748b",
        reverseButtons: true,
        focusConfirm: false,
        backdrop: "rgba(2,6,23,.55)"
      });
    }

    $(".gizle").click(function (e) {
      e.preventDefault();
      var tur = $(this).data("gizletur");
      $.post("/api/gorunurluk", { tur: tur }, function (response) {
        if (response.code === 200) location.reload();
      });
    });

    var maxTarih = new Date().toISOString().split("T")[0];
    $("#baslangicTarihi, #bitisTarihi").attr("max", maxTarih);

    $("#baslangicTarihi").on("change", function () {
      var baslangic = $(this).val();
      if (!baslangic) return;
      $("#bitisTarihi").attr("min", baslangic);
      var mevcutBitis = $("#bitisTarihi").val();
      if (mevcutBitis && mevcutBitis < baslangic) $("#bitisTarihi").val(baslangic);
    });

    $("#filtreUygula").on("click", function () {
      var baslangic = $("#baslangicTarihi").val();
      var bitis = $("#bitisTarihi").val();

      if (!baslangic || !bitis) {
        toastr.warning("Lütfen başlangıç ve bitiş tarihlerini seçin.");
        return;
      }
      if (bitis < baslangic) {
        toastr.error("Bitiş tarihi, başlangıç tarihinden önce olamaz.");
        return;
      }

      $.post("/api/tarihfiltre", {
        baslangictarihifiltre: baslangic,
        bitistarihifiltre: bitis
      }, function (response) {
        if (response && response.code === 200) {
          toastr.success("Tarih filtresi uygulandı.");
          location.reload();
        } else {
          toastr.error("Tarih filtresi uygulanamadı.");
        }
      });
    });
  });
})();
</script>