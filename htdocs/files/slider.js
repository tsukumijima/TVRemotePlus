  $(function(){

    var galleryThumbs = new Swiper('#broadcast-tab-box', {
      slidesPerView: 'auto',
      watchSlidesVisibility: true,
      watchSlidesProgress: true,
      slideActiveClass: 'swiper-slide-active'
    });
    galleryThumbs.on('tap', function () {
      var current = galleryTop.activeIndex;
      galleryThumbs.slideTo(current, 500, true);
    });
    var galleryTop = new Swiper('#broadcast-box', {
      autoHeight: true,
      thumbs: {
        swiper: galleryThumbs
      }
    });

  });
