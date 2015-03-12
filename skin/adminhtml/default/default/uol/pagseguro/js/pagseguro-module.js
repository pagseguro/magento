 /* ************************************* */
/* *********** MESSAGES **************** */
/* ************************************* */
var Messages = new function() {
    
    var wrapper = jQuery("#pagseguro-module-contents");

    var getHtml = function(options) {
        return '<div id="'+ options.id +'" class="pagseguro-msg pagseguro-msg-'+options.type+' pagseguro-msg-'+options.size+'"><'+options.tag+'>' + options.message + '</'+options.tag+'></div>';
    }

    var remove = function() {
        wrapper.find('.pagseguro-msg-error, .pagseguro-msg-success').remove();
    };

    var add = function(message, type) {
        var html = getHtml({
            id: 'pagseguro-main-message',
            message: message,
            type: type,
            size: 'small',
            tag: 'p'
        });
        remove();
        wrapper.prepend(html);
    };

    return {
        addError: function(message) {
            add(message, 'error');
        },
        addSuccess: function(message) {
            add(message, 'success');
        },
        remove: function() {
            remove();
        },
        getHtml: function(options) {
            return getHtml(options);
        }
    };

};


/* ************************************* */
/* *********** MODAL **************** */
/* ************************************* */
var Modal = new function(){
    
    var opened = false;
    
    var defaults = {
        transition:"none",speed:300,initialWidth:"600",innerWidth:"525",initialHeight:"450",title:!1,opacity:.65,close:"fechar <strong>x</strong>",fixed:true
    };
    
    var _bindEvents = function(elements,o){
        var options = jQuery.extend({},defaults,o || {});
        $(elements).colorbox(options);
    };

    var open = function(o) {
        var options = jQuery.extend({},defaults,o || {});
        if( options.inline && options.avoidDefault ){
            if( !options.width && !options.innerHeight ){
                options.innerWidth = parseInt(jQuery( options.href ).css('width').replace('px','')) + parseInt(jQuery( options.href ).css( 'padding-left' ).replace('px','')) + parseInt(jQuery( options.href ).css( 'padding-right' ).replace('px',''))
            }
            if( !options.height && !options.innerHeight  ){
                options.innerHeight = parseInt(jQuery( options.href ).css('height').replace('px','')) + parseInt(jQuery( options.href ).css( 'padding-top' ).replace('px','')) + parseInt(jQuery( options.href ).css( 'padding-bottom' ).replace('px',''));
            }
        }
        jQuery.colorbox(options);
    };

    var showLoading = function() {
        if (jQuery('#pagseguro-loading-message:visible').length > 0) {
            return false;
        }
        var html = Messages.getHtml({
            id: 'pagseguro-loading-message',
            type: 'loading',
            size: 'medium',
            message: 'Aguarde...',
            tag: 'h3'
        });
        Messages.remove();
        open({
            html: html,
            width:  600,
            height: 600,
            overlayClose: false,
            escKey: false,
            close: false
        });
        jQuery('#cboxClose').hide();
        resize();
    };

    var hideLoading = function(callback) {
        close(callback);
    };

    var message = function(type, message) {
        var html = Messages.getHtml({
            type: type,
            size: 'small',
            message: message,
            tag: 'h3'
        });
        open({
            html: html,
            width:  400,
            height: 400
        });
        resize();
    };

    var resize = function() {
        jQuery.colorbox.resize();
    };

    var close = function(callback) {
        jQuery.colorbox.close(callback);
    };

    var remove = function() {
        jQuery.colorbox.remove();
    };

    return {
        close : close,
        remove : remove,
        open : open,
        resize : resize,
        showLoading: showLoading,
        hideLoading: hideLoading,
        message: message
    }

};
/* ************************************* */
/* *********** MENU **************** */
/* ************************************* */
var Menu = new function() {

    var wrapper  = jQuery("#pagseguro-module-menu");
    var saveForm = $("#pagseguro-save-wrapper");
    var body = $("html, body");
    var windowSel  = jQuery(window);
    var animating = false;

    var applyMenu = function() {

        var selectedClass = "selected";
        var allItems = wrapper.find(".menu-item");

        allItems.click(function(e){
            
            e.preventDefault();
            e.stopPropagation();
            
            if (!animating) {

                animating = true;

                var item = jQuery(this);
                var id = item.attr("data-page-id");
                var hasForm = item.attr("data-has-form");

                allItems.removeClass(selectedClass);
                item.addClass(selectedClass);

                var showNewPage = function() {
                    
                    Messages.remove();

                    jQuery(".pagseguro-module-content").removeClass(selectedClass);
                    jQuery("#pagseguro-module-content-" + id).addClass(selectedClass);
                    
                    if (hasForm) {
                        saveForm.show();
                    } else {
                        saveForm.hide();
                    }

                    jQuery("#current-page-id").val(id);
                    animating = false;

                };

                if (windowSel.scrollTop() > 100) {
                    
                    body.animate({scrollTop:0}, 800, 'swing', function(){
                        setTimeout(showNewPage, 100);
                    });

                } else {
                    showNewPage();
                }

            };

            return false;

        });

    };

    var applyFixedPostion = function() {
        
        var initialPos      = wrapper.offset().top;
        var initialLeft     = wrapper.offset().left;
        var initialWidth    = wrapper.width();
        var fixedClass      = 'fixed';

        var resetFixed = function() {
            wrapper.css('width', '');
            wrapper.css('top', '');
            wrapper.removeClass(fixedClass);
        };

        var applyFixed = function(top) {
            if (!wrapper.hasClass('fixed')) {
                wrapper.addClass(fixedClass);
            }
            wrapper.css('top', parseInt(top - initialPos, 10) + 'px');
            wrapper.width(initialWidth);
        };

        var getWindowTop = function() {
            var aditionalSum = jQuery(".page-head").length > 0 ? 100 : 0;
            return windowSel.scrollTop() + aditionalSum;
        };

        windowSel.scroll(function(e){

            var top = getWindowTop();

            if (top >= initialPos) {
                applyFixed(top);
            } else {
                resetFixed();
            }

        });

        windowSel.resize(function(){
            var wasFixed = wrapper.hasClass(fixedClass);
            resetFixed();
            initialWidth = wrapper.width();
            if (wasFixed){
                applyFixed(getWindowTop());
            }
        });

    };

    var applyGotoConfig = function() {
        jQuery(".pagseguro-goto-configuration").click(function(){
            jQuery("#menu-item-1").trigger('click');
            jQuery("#pagseguro-email-input").focus();
        });
    };

    this.init = function(){
        applyFixedPostion();
        applyMenu();
        applyGotoConfig();
    };

};

/* ************************************* */
/* *********** CHECKBOXES **************** */
/* ************************************* */
function checkboxes() {

    var j = 0;
    var ckbTrue = 0;

    jQuery('input[name="send_emails[]"]').each(function() {
        if (jQuery(this).is(':checked') == true) {
            ckbTrue++;
        }
        j++;
    }); 
    
    jQuery('input[name="conciliation_orders[]"]').each(function() {
        if (jQuery(this).is(':checked') == true) {
            ckbTrue++;
        }
        j++;
    }); 
    
    if (j == ckbTrue) {
        jQuery(':checkbox').prop('checked','');
        jQuery('#send-email-button').prop("disabled", true);
        jQuery('#conciliation-button').prop("disabled", true);
    } else {
        jQuery(':checkbox').prop('checked','checked');
        jQuery('#send-email-button').prop("disabled", false);
        jQuery('#conciliation-button').prop("disabled", false);
    }
} 

