/* jshint undef: false, unused:false */
// AJAX Functions
var jq = jQuery;
var id, type, curXhr;
jq(document).ready(function bpLike() {
    "use strict";
    jq(document).on('click', '.like, .unlike', function() {

        if (curXhr) {
          return false;
        }

        id = jq(this).attr('id');                           // Used to get the id of the entity liked or unliked

        type = jq(this).data('like-type');

        var method = jq(this).hasClass('like')?'like':'unlike';

        var countEl = jq(this).find('.like-count');
        var count = parseInt(countEl.text()||0);
        jq(this).addClass('loading');


        // may only need one if and else if
        // if (like) {} else if (unlike) {} else {oops()}
        // leave for now as may need something for messages
        if (method == 'like') {
            jq(this).removeClass('like')
                .addClass('unlike')
                .attr('title', bplikeTerms.unlike_message);

                countEl.text(count+1);
        } else if (method == 'unlike') {

            jq(this).removeClass('unlike')
                .addClass('like')
                .attr('title', bplikeTerms.like_message);

            countEl.text(count-1||'');
        }

        curXhr = jq.post(ajaxurl, {
            action: 'activity_like',
            'type': type,
            'method': method,
            'id': id
        },
            function( data ) {
                jq('#' + id).fadeOut(100, function() {
                    jq(this).html(data).removeClass('loading').fadeIn(100);
                });

                if (type != 'activity_comment')
                    getLikes(id, type);

                // Nobody else liked this, so clear who likes the item
                // TODO: make this work with different text/templates
                if ( data == 'Like <span>0</span>' ) {
                    jq('#users-who-like-' + getItemId(id) ).empty();
                }

                type = type
                .replace( 'unlike', '' )
                .replace( 'like', '' )
                .trim();
                // Show who likes the item if user is first to like
                // TODO: make this work with different text/templates
                /*if (data == 'Unlike <span>1</span>') {
                    jq('#users-who-like-' + getItemId(id)).html('<small>' + bplikeTerms.you_like_this +'</small>');
                }*/
                curXhr = false;
            });

        return false;
    });

    function getItemId(id) {
        return id
          .replace('bp-like-', '')
          .replace('activity-', '')
          .replace('blogpost-', '')
          .replace('comment-', '')
          .replace('bbp-reply-', '')
          .replace('un', '');
    }

    // this function is to get likes of a post
    function getLikes( id, type ) {
      id = getItemId(id);
      type = type
           .replace('like', '')
           .replace('unlike' , '')
           .replace('un', '')
           .trim();

      jq('#users-who-like-' + id).addClass('loading');
      jq.post(ajaxurl, {
          action: 'bplike_ult_get_likes',
          'type': type,
          'id': id
      }, function( response ) {
        response = response
          .replace('<p class="users-who-like" id="users-who-like-' + id + '">', '')
          .replace('</p>', '');

        jq('#users-who-like-' + id).html(response);
        jq('#users-who-like-' + id).removeClass('loading');

      })
    };
});
