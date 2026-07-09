(function ($) {
  function renderImagePreview($target, urls) {
    $target.empty();

    urls.forEach(function (url) {
      if (url) {
        $('<img>', { src: url, alt: '' }).appendTo($target);
      }
    });
  }

  $(function () {
    var logoFrame;
    var galleryFrame;

    $('[data-partner-logo-button]').on('click', function (event) {
      event.preventDefault();

      if (logoFrame) {
        logoFrame.open();
        return;
      }

      logoFrame = wp.media({
        title: 'Partner logó kiválasztása',
        button: { text: 'Logó használata' },
        multiple: false
      });

      logoFrame.on('select', function () {
        var attachment = logoFrame.state().get('selection').first().toJSON();
        var previewUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

        $('[data-partner-logo-id]').val(attachment.id);
        renderImagePreview($('[data-partner-logo-preview]'), [previewUrl]);
      });

      logoFrame.open();
    });

    $('[data-partner-logo-clear]').on('click', function (event) {
      event.preventDefault();
      $('[data-partner-logo-id]').val('');
      $('[data-partner-logo-preview]').empty();
    });

    $('[data-partner-gallery-button]').on('click', function (event) {
      event.preventDefault();

      if (galleryFrame) {
        galleryFrame.open();
        return;
      }

      galleryFrame = wp.media({
        title: 'Partner galéria kiválasztása',
        button: { text: 'Galéria használata' },
        multiple: true
      });

      galleryFrame.on('select', function () {
        var ids = [];
        var urls = [];

        galleryFrame.state().get('selection').each(function (attachment) {
          var image = attachment.toJSON();
          var previewUrl = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;

          ids.push(image.id);
          urls.push(previewUrl);
        });

        $('[data-partner-gallery-ids]').val(ids.join(','));
        renderImagePreview($('[data-partner-gallery-preview]'), urls);
      });

      galleryFrame.open();
    });

    $('[data-partner-gallery-clear]').on('click', function (event) {
      event.preventDefault();
      $('[data-partner-gallery-ids]').val('');
      $('[data-partner-gallery-preview]').empty();
    });
  });
})(jQuery);
