(function($){
    wp.customize( 'newsletter_title', function(value) {
        value.bind(function(to) {
            $( '#newsletter-title').html(to);
        });
    });
    wp.customize( 'newsletter_content', function(value) {
        value.bind(function(to) {
            $('#newsletter-content').html(to);
        });
    });
}(jQuery));
