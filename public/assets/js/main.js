$('#js-video-box img ,#js-video-box .bt-play ,#js-video').click(function (e) { 
    $('#js-video')[0].play();
    $('#js-video-box video').removeClass('opacity-0');
});