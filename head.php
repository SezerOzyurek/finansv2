<?php
// head.php - Global Head Includes, Tailwind Config & Theme Initialization
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        fontFamily: {
          sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
        },
        colors: {
          slate: {
            850: '#111827',
            925: '#0b0f19',
            950: '#070a11',
          }
        }
      }
    }
  }
</script>
<script>
  (function() {
    var savedTheme = localStorage.getItem('finans_theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  })();
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.36.1/dist/tabler-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
  html {
    font-size: 14.5px;
    font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    font-feature-settings: 'cv02' 1, 'cv03' 1, 'cv04' 1, 'cv11' 1;
    letter-spacing: -0.015em;
  }

  /* Unify Table Head and Cell Spacing */
  table.dataTable thead th,
  table.dataTable tbody td {
    padding: 10px 14px !important;
  }

  /* Elite Fintech Tabular Numbers for Currency and Metrics */
  .tabular-nums, [data-money-amount], table td, .font-mono, #sessionCountdown {
    font-variant-numeric: tabular-nums;
    font-feature-settings: 'tnum' 1, 'zero' 1;
    letter-spacing: -0.02em;
  }
  
  /* Smooth theme transitions */
  body {
    transition: background-color 0.2s ease, color 0.2s ease;
  }

  /* Universal Dark Mode Input, Select & Textarea Defaults */
  .dark input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="hidden"]),
  .dark select,
  .dark textarea {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
  }
  .dark input::placeholder,
  .dark textarea::placeholder {
    color: #94a3b8 !important;
  }
  .dark input:focus,
  .dark select:focus,
  .dark textarea:focus {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
    outline: none !important;
  }
  .dark select option {
    background-color: #0f172a !important;
    color: #f8fafc !important;
  }

  /* Custom Clean Scrollbar */
  ::-webkit-scrollbar { width: 6px; height: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 9999px; }
  ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.5); }
  .dark ::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); }
  .dark ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.4); }

  /* DataTables Dark Mode Overrides */
  .dark .dataTables_wrapper { color: #cbd5e1; }
  .dark .dataTables_wrapper .dataTables_length,
  .dark .dataTables_wrapper .dataTables_filter,
  .dark .dataTables_wrapper .dataTables_info,
  .dark .dataTables_wrapper .dataTables_processing,
  .dark .dataTables_wrapper .dataTables_paginate { color: #94a3b8 !important; }
  .dark .dataTables_wrapper .dataTables_filter input {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
    border-radius: 0.75rem !important;
    padding: 0.35rem 0.75rem !important;
  }
  .dark .dataTables_wrapper .dataTables_length select {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
    border-radius: 0.75rem !important;
    padding: 0.25rem 0.5rem !important;
  }
  .dark table.dataTable { border-color: #1e293b !important; }
  .dark table.dataTable thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom: 1px solid #1e293b !important;
  }
  .dark table.dataTable tbody tr { background-color: transparent !important; }
  .dark table.dataTable tbody td { border-bottom: 1px solid #1e293b !important; color: #e2e8f0; }
  .dark table.dataTable.stripe tbody tr.odd,
  .dark table.dataTable.display tbody tr.odd { background-color: rgba(255,255,255,0.02) !important; }
  .dark table.dataTable tbody tr:hover,
  .dark table.dataTable.hover tbody tr:hover,
  .dark table.dataTable.display tbody tr:hover,
  .dark table.dataTable.display tbody tr:hover > .sorting_1,
  .dark table.dataTable.order-column.hover tbody tr:hover > .sorting_1,
  .dark table.dataTable tbody tr:hover td,
  .dark table.dataTable.display tbody tr:hover td {
    background-color: #1e293b !important;
    color: #f8fafc !important;
  }
  .dark table tbody tr:hover {
    background-color: #1e293b !important;
  }
  .dark table tbody tr:hover td {
    background-color: transparent !important;
  }
  .dark .dataTables_wrapper .dataTables_paginate .paginate_button {
    color: #94a3b8 !important;
    border-radius: 0.5rem !important;
  }
  .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
  }
  .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #334155 !important;
    border-color: #475569 !important;
    color: #fff !important;
  }
</style>

