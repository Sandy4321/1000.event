$(document).keydown(function (eventObject) {
    if (eventObject.which == 27) {
        if (gallery) {
            $('#gallery .gallery-control-close').trigger('click');
        }
    }
    if (eventObject.which == 39) {
        if (gallery) {
            $('#gallery .Next').trigger('click');
        }
    }
    if (eventObject.which == 37) {
        if (gallery) {
            $('#gallery .Prev').trigger('click');
        }
    }
});

function image_gallery() {
    var currentIndex;

    var $el = $(this);
    var els = $('.item-photo');



    function getIndex(element) {
        return $('.item-photo').toArray().indexOf(element)
        //return $('.item-file[data-media-type=1]:not(.item-deleted)').toArray().indexOf(element);
    }

    function updateIndexes(prop) {
        if (prop === 'initial') {
            currentIndex = getIndex($el.get(0));
        }
        else if (prop === 'Next') {
            currentIndex = currentIndex !== els.length - 1 ? currentIndex + 1 : 0;
        }
        else {
            currentIndex = currentIndex !== 0 ? currentIndex - 1 : els.length - 1;
        }
    }

    var indexesGetters = {
        getCurrentImageId : function() {
            return $(els[currentIndex]).data('id');
        },
        getCurrentImageThumbnailSrc: function() {
            return $(els[currentIndex]).data('thumbnailbig');
        },
        getCurrentImageFileName : function() {
            var name = $(els[currentIndex]).data('name');
            return name;
        }
    };


    updateIndexes('initial');
    gallery = true;
    initialize();

    $('#gallery-image').attr('src', indexesGetters.getCurrentImageThumbnailSrc());
    $('#gallery').show();
    $('#gallery .gallery-control').on('click', handler);
    $('#gallery .gallery-control-close').on('click', close_galery);

    function close_galery() {
        $('#gallery .gallery-control').off('click', handler);
        $('#gallery .gallery-control-close').off('click', close_galery);
        $('#gallery').hide();
        gallery = false;
    }

    function initialize() {
        $('#gallery-paginator').html((currentIndex + 1) + ' / ' + els.length);
        $('#gallery-caption').html($(els[currentIndex]).data('name'));
        $('#gallery-image').attr('src', indexesGetters.getCurrentImageThumbnailSrc());

        var btn_like = $(els[currentIndex]).parent('.photo').children('.btn-like');
        $('#gallery-like').html('');
        btn_like.clone().appendTo($('#gallery-like'));

    }


    function handler() {
        var arrow = $(this);
        var changeType = arrow.hasClass('Next') ? 'Next' : "Prev";
        updateIndexes(changeType);
        initialize();
    }

    return false;
}