<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/js/app.js')
    <x-inertia::head />
    <style>
        #toast-container{position:fixed;top:72px;right:16px;z-index:9999;display:grid;gap:8px;max-width:360px;pointer-events:none}
        .toast{display:flex;align-items:center;gap:8px;border-radius:8px;padding:12px 16px;font-family:Inter,sans-serif;font-size:13px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.14);transform:translateX(120%);opacity:0;transition:transform .3s ease,opacity .3s ease;pointer-events:auto}
        .toast--show{transform:translateX(0);opacity:1}
        .toast--success{background:#dcfce7;color:#166534}.toast--error{background:#fee2e2;color:#991b1b}.toast--info{background:#e0f2fe;color:#075985}.toast--warning{background:#fff7d6;color:#8a5a00}
        .toast__icon{font-size:20px;flex-shrink:0}.toast__msg{flex:1}
    </style>
</head>

<body>
    <x-inertia::app id="custom-app-id" />
    <div id="toast-container"></div>
    <script>
        function showToast(msg, type) {
            var c = document.getElementById('toast-container');
            if (!c) return;
            var e = document.createElement('div');
            e.className = 'toast toast--' + type;
            e.innerHTML = '<span class="toast__icon material-symbols-outlined">' + ({success:'check_circle',error:'error',info:'info',warning:'warning'}[type]||'info') + '</span><span class="toast__msg">' + msg + '</span>';
            c.appendChild(e);
            requestAnimationFrame(function(){ e.classList.add('toast--show') });
            setTimeout(function(){
                e.classList.remove('toast--show');
                setTimeout(function(){ e.remove() }, 400);
            }, 4000);
        }
        document.addEventListener('inertia:success', function(e) {
            var flash = e.detail.page.props.flash || {};
            if (flash.success) showToast(flash.success, 'success');
            if (flash.error) showToast(flash.error, 'error');
        });
    </script>
</body>

</html>
