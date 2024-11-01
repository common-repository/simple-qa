jQuery(document).ready(function($) {
  $(".question").click(function() {
    if (jQuery(".g-recaptcha").is(":hidden")) {
      jQuery(".g-recaptcha").slideDown("slow");
    }
    if (jQuery(".userdatafields").is(":hidden")) {
      jQuery(".userdatafields").slideDown("slow");
    }
  });
});

jQuery(document).ready(function($) {
  $('ul.akkordeon li > p:first').addClass('active').next('div').slideDown(200);
  $('ul.akkordeon li > p').click(function() {
    if (!$(this).hasClass('active')) {
      $('ul.akkordeon li > p').removeClass('active').next('div').slideUp();
      $(this).addClass('active');
      $(this).next('div').slideDown(200);
    } else {
      $(this).removeClass('active').next('div').slideUp();
    }
  });
});
