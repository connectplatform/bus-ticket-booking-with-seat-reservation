jQuery(document).ready(function(){
    jQuery('.bus-stops-wrapper input.text').timepicker({
        timeFormat: 'H:i',
        interval: 15,
        minTime: '00:00',
        maxTime: '23:59',
        dynamic: true,
        dropdown: true,
        scrollbar: true,
        orientation: 'c',
        listWidth: 1
    });   

    jQuery('.pickpoint-adding input[type="text"]').timepicker({
        timeFormat: 'H:i',
        interval: 15,
        minTime: '00:00',
        maxTime: '23:59',
        dynamic: true,
        dropdown: true,
        scrollbar: true,
        orientation: 'c',
        listWidth: 1
    });  
});