<script>
    window.addEventListener('open-new-tab', event => {
        const url = event.detail.url || (event.detail[0] && event.detail[0].url);
        if (url) {
            const newWindow = window.open(url, '_blank');
            if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                alert('Popup blocked! Please allow popups for this site to view as agent.');
            }
        }
    });
</script>
