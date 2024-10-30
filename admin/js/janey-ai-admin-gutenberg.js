const { subscribe } = wp.data;
var janey = janey || {};
janey.noticed = false;
subscribe(function() {
    janey.saved = wp.data.select('core/notices').getNotices().find(function(el) {
        return el.id === 'SAVE_POST_NOTICE_ID';
    });
    if (janey.saved && !janey.noticed) {
        janey.noticed = true;
        var options = {
            'actions': [{
                'label': 'refresh the page.',
                'onClick': function() { window.location.reload(); },
                'url': '#'
            }]
        };
        wp.data.dispatch('core/notices').createNotice('warning', 'To see updates to Janey AI Tags, please:', options);
    }
})
