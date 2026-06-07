(function($){
    $(document).ready(function(){
        // Link display toggle
        $('#show-ecs-link').on('change', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    'action': 'ecs_show_link',
                    'value': !! $('#show-ecs-link:checked').length,
                    'nonce': $('#ecs-link-nonce').val()
                },
                success: function(data) {
                    $('#ecs-link-display .toggle-message').show();
                    setTimeout(function() {
                        $('#ecs-link-display .toggle-message').hide();
                    }, 5000);
                }
            });
        });

        // Tab switching
        $('.ecs-tab').on('click', function() {
            var tab = $(this).data('tab');

            $('.ecs-tab').removeClass('ecs-tab-active border-[#EB6924] text-[#EB6924]')
                         .addClass('border-transparent text-gray-500');

            $(this).addClass('ecs-tab-active border-[#EB6924] text-[#EB6924]')
                   .removeClass('border-transparent text-gray-500');

            $('.ecs-tab-panel').addClass('hidden');
            $('#tab-' + tab).removeClass('hidden');
        });
    });
})(jQuery);
