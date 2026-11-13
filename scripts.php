<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  html, body { overflow-x: hidden; }
  body { margin: 0; }

  /* Lightbox Styles */
  .ux-lightbox.hidden { display: none; }
  .ux-lightbox { position: fixed; inset: 0; z-index: 9999; }
  .ux-lightbox__backdrop { position: absolute; inset: 0; background: rgba(2,6,23,.82); backdrop-filter: blur(4px); }
  .ux-lightbox__panel { position: relative; height: 100%; width: 100%; display: grid; place-items: center; padding: 18px; }
  .ux-lightbox__img { max-height: calc(100vh - 120px); max-width: min(1100px, 92vw); border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,.5); background: #0b1220; }
  .ux-lightbox__bar { position: absolute; top: 14px; right: 14px; display: flex; gap: 8px; }
  .ux-lightbox__btn { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); border-radius: 12px; padding: 8px 12px; font-weight: 700; font-size: 13px; }
  .ux-lightbox__btn:hover { background: rgba(255,255,255,.20); }

  /* Upload Overlay */
  .ux-upload-overlay.hidden { display: none; }
  .ux-upload-overlay { position: fixed; inset: 0; z-index: 9998; display: grid; place-items: center; background: rgba(2,6,23,.6); backdrop-filter: blur(3px); }
  .ux-upload-overlay__card { width: min(480px, calc(100vw - 2rem)); background: #ffffff; border: 1px solid rgba(226,232,240,.9); border-radius: 20px; padding: 20px; box-shadow: 0 30px 90px rgba(0,0,0,.3); }
  .dark .ux-upload-overlay__card { background: #0f172a; border-color: #334155; }
  .ux-upload-overlay__title { font-weight: 800; letter-spacing: -0.01em; color: rgb(15 23 42); }
  .dark .ux-upload-overlay__title { color: #f8fafc; }
  .ux-upload-overlay__sub { margin-top: 6px; font-size: 13px; color: rgb(71 85 105); }
  .dark .ux-upload-overlay__sub { color: #94a3b8; }
  .ux-spin { width: 18px; height: 18px; border: 2px solid rgb(148 163 184); border-top-color: rgb(16 185 129); border-radius: 999px; display: inline-block; animation: uxspin .8s linear infinite; }
  @keyframes uxspin { to { transform: rotate(360deg); } }

  /* Toastr Theme Modern */
  #toast-container > div {
    border-radius: 16px !important;
    padding: 14px 16px !important;
    box-shadow: 0 16px 40px rgba(0, 0, 0, .2) !important;
    opacity: 1 !important;
    font-family: inherit !important;
  }
  #toast-container > .toast {
    background: #0f172a !important;
    border: 1px solid #1e293b !important;
    color: #f8fafc !important;
    backdrop-filter: blur(8px);
  }
  #toast-container > .toast-success { border-color: rgba(16, 185, 129, .5) !important; }
  #toast-container > .toast-error { border-color: rgba(244, 63, 94, .5) !important; }
  #toast-container > .toast-warning { border-color: rgba(245, 158, 11, .5) !important; }
  #toast-container .toast-title { font-weight: 800; font-size: 13px; }
  #toast-container .toast-message { font-weight: 500; font-size: 12px; }

  /* SweetAlert2 Modern Theme */
  .swal2-popup {
    border-radius: 24px !important;
    border: 1px solid rgba(226, 232, 240, .85) !important;
    box-shadow: 0 25px 70px rgba(0, 0, 0, .2) !important;
    font-family: inherit !important;
  }
  .dark .swal2-popup {
    background: #0f172a !important;
    border-color: #1e293b !important;
    color: #f8fafc !important;
  }
  .swal2-title { font-weight: 800 !important; letter-spacing: -0.01em; color: #0f172a !important; font-size: 18px !important; }
  .dark .swal2-title { color: #f8fafc !important; }
  .swal2-html-container { color: #475569 !important; font-size: 13px !important; }
  .dark .swal2-html-container { color: #94a3b8 !important; }
  .swal2-actions { gap: 8px !important; }
  .swal2-styled { border-radius: 12px !important; font-weight: 700 !important; font-size: 13px !important; }

  /* Tooltip */
  .js-money-tooltip[data-money-amount] {
    cursor: help;
    text-decoration: underline;
    text-decoration-style: dotted;
    text-decoration-color: rgba(148, 163, 184, .8);
    text-underline-offset: 3px;
  }
  .money-tip-floating.hidden { display: none; }
  .money-tip-floating { position: fixed; z-index: 10020; pointer-events: none; max-width: min(320px, calc(100vw - 24px)); }
  .money-tip-box {
    border-radius: 16px;
    background: #0f172a;
    border: 1px solid #1e293b;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
    color: #f8fafc;
    padding: 12px 14px;
    font-size: 12px;
  }
  .dark .money-tip-box { background: #0b0f19; border-color: #334155; }
</style>

<script>
// Global Theme & Mobile Navigation Controller
(function() {
  // Theme Toggle Controller
  var toggleBtn = document.getElementById('themeToggleBtn');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      var html = document.documentElement;
      var isDark = html.classList.toggle('dark');
      localStorage.setItem('finans_theme', isDark ? 'dark' : 'light');
      window.dispatchEvent(new CustomEvent('themeChanged', { detail: { isDark: isDark } }));
    });
  }

  // Mobile Sidebar Drawer Controller
  var openBtn = document.querySelector('[data-sidebar-open]');
  var closeBtn = document.querySelector('[data-sidebar-close]');
  var overlay = document.getElementById('sidebarOverlay');
  var sidebar = document.getElementById('sidebar');

  function openSidebar() {
    if (sidebar && overlay) {
      sidebar.classList.remove('-translate-x-full');
      overlay.classList.remove('hidden');
    }
  }

  function closeSidebar() {
    if (sidebar && overlay) {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
    }
  }

  if (openBtn) openBtn.addEventListener('click', openSidebar);
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);

  // Rakam Gizleme / Gösterme
  $(document).on('click', '.gizle', function(e) {
    e.preventDefault();
    var tur = $(this).data('gizletur');
    $.post('api/index.php?path=gorunurluk', { tur: tur }, function(response) {
      window.location.reload();
    });
  });

  // Chart.js Dark Mode Helper
  window.getChartColors = function() {
    var isDark = document.documentElement.classList.contains('dark');
    return {
      textColor: isDark ? '#94a3b8' : '#64748b',
      gridColor: isDark ? 'rgba(148, 163, 184, 0.08)' : 'rgba(226, 232, 240, 0.7)',
      tooltipBg: isDark ? '#0f172a' : '#0f172a',
      isDark: isDark
    };
  };

  // Asgari Ücret / Enflasyon / Döviz Karşılıkları Tooltip Controller
  var tipEl = null;
  var tipCache = {};
  var activeTrigger = null;

  function ensureTooltipEl() {
    if (tipEl) return tipEl;
    tipEl = document.createElement('div');
    tipEl.id = 'moneyTipFloating';
    tipEl.className = 'money-tip-floating hidden';
    tipEl.innerHTML = '<div class="money-tip-box" id="moneyTipContent"></div>';
    document.body.appendChild(tipEl);
    return tipEl;
  }

  function positionTooltip(target) {
    if (!tipEl || !target) return;
    var rect = target.getBoundingClientRect();
    var tipRect = tipEl.getBoundingClientRect();
    var pad = 8;

    var left = rect.left + (rect.width / 2) - (tipRect.width / 2);
    left = Math.max(pad, Math.min(left, window.innerWidth - tipRect.width - pad));

    var top = rect.top - tipRect.height - 8;
    if (top < pad) {
      top = rect.bottom + 8; // aşağıya göster
    }

    tipEl.style.left = Math.round(left) + 'px';
    tipEl.style.top = Math.round(top) + 'px';
  }

  function renderTooltipContent(title, items, footer) {
    var html = '<div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 pb-1.5 border-b border-slate-700/60 mb-2 flex items-center justify-between">';
    html += '<span><i class="ti ti-scale text-emerald-400 mr-1"></i>' + (title || 'Karşılıklar') + '</span>';
    html += '<span class="text-[10px] text-slate-500 font-normal">TCMB / Asgari</span></div>';
    html += '<div class="space-y-1.5 text-xs">';
    if (items && items.length) {
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var isAsgari = it.label && it.label.indexOf('Asgari') !== -1;
        html += '<div class="flex items-center justify-between gap-4">';
        html += '<span class="' + (isAsgari ? 'font-bold text-amber-300' : 'text-slate-300') + '">' + (it.label || '') + ':</span>';
        html += '<span class="font-extrabold tabular-nums ' + (isAsgari ? 'text-amber-400' : 'text-white') + '">' + (it.value || '-') + '</span>';
        html += '</div>';
      }
    } else {
      html += '<div class="text-slate-400">Karşılık bilgisi bulunamadı.</div>';
    }
    html += '</div>';
    if (footer) {
      html += '<div class="mt-2 pt-1 border-t border-slate-700/40 text-[10px] text-slate-400">' + footer + '</div>';
    }
    return html;
  }

  function showTooltip(el) {
    activeTrigger = el;
    var amount = el.getAttribute('data-money-amount');
    var date = el.getAttribute('data-money-date') || '';
    var context = el.getAttribute('data-money-context') || 'generic';

    if (!amount) return;

    var container = ensureTooltipEl();
    var contentEl = document.getElementById('moneyTipContent');
    var cacheKey = amount + '|' + date + '|' + context;

    if (tipCache[cacheKey]) {
      contentEl.innerHTML = tipCache[cacheKey];
      container.classList.remove('hidden');
      positionTooltip(el);
      return;
    }

    contentEl.innerHTML = '<div class="flex items-center gap-2 py-1 text-xs text-slate-300"><span class="ux-spin"></span><span>Hesaplanıyor...</span></div>';
    container.classList.remove('hidden');
    positionTooltip(el);

    $.getJSON('para_detay.php', { amount: amount, date: date, context: context }, function(resp) {
      if (activeTrigger !== el) return;
      if (resp && resp.code === 200 && resp.data) {
        var html = renderTooltipContent(resp.data.title, resp.data.items, resp.data.footer);
        tipCache[cacheKey] = html;
        contentEl.innerHTML = html;
        positionTooltip(el);
      } else {
        contentEl.innerHTML = '<div class="text-xs text-rose-400">Veri alınamadı.</div>';
        positionTooltip(el);
      }
    }).fail(function() {
      if (activeTrigger !== el) return;
      contentEl.innerHTML = '<div class="text-xs text-rose-400">Bağlantı hatası.</div>';
      positionTooltip(el);
    });
  }

  function hideTooltip() {
    activeTrigger = null;
    if (tipEl) {
      tipEl.classList.add('hidden');
    }
  }

  $(document).on('mouseenter focusin', '.js-money-tooltip[data-money-amount]', function() {
    showTooltip(this);
  });

  $(document).on('mouseleave focusout', '.js-money-tooltip[data-money-amount]', function() {
    hideTooltip();
  });
})();
</script>
