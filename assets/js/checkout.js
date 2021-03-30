(function($) {
    'use strict';
    $( function() {
        $('form.checkout').on('click', '#place_order', function () {
            console.log('on click')
            return false;
        });
    })
})(jQuery)