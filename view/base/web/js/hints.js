/**!
 * Copyright (c) 2016 H&O E-commerce specialisten B.V. (http://www.h-o.nl/)
 * See LICENSE.txt for license details.
 */

document.addEventListener("DOMContentLoaded", function (event) {
    require(['jquery', 'underscore', 'ko'], function ($, _, ko) {
        "use strict"

        $(document).keydown(function (event) {
            if (!event.shiftKey) {
                return
            }

            var hintElements = $('[data-ho-hinttype], .ho-hint')

            hintElements.each(function () {
                var hintElem = $(this);
                var hintType = hintElem.data('ho-hinttype');
                hintElem.removeAttr('data-ho-hinttype');

                hintElem.data('ho-hintdata');
                hintElem.removeAttr('data-ho-hintdata')

                hintElem.addClass('ho-hint')
                    .addClass('ho-hint-outline')
                    .addClass('ho-hint-' + hintType)
            });

            $('*').each(function(){
                var knockoutData = ko.dataFor(this)
                if (!knockoutData) {
                    return
                }

                var elem = $(this);
                var parentKnockoutData = ko.dataFor(this.parentElement);
                if (parentKnockoutData && knockoutData.component == parentKnockoutData.component) {
                    return;
                }

                elem.addClass('ho-hint ho-hint-outline ho-hint-knockout')
                elem.data('ho-hinttype', 'knockout')

            });
        })


        //Remove styles when no
        $(document).keyup(function (event) {
            if (event.shiftKey) {
                return
            }

            $('.ho-hint').removeClass('ho-hint-outline ho-hint-hover')
        })


        $(document).on('mouseover', '.ho-hint', function (event) {
            if (!event.shiftKey) {
                return
            }

            $(this).addClass('ho-hint-hover')
        })


        $(document).on('mouseout', '.ho-hint', function (event) {
            if (!event.shiftKey) {
                return
            }

            $(this).removeClass('ho-hint-hover')
        })


        $(document).on('click', '.ho-hint', function (event) {
            if (!event.shiftKey) {
                return
            }

            hint(this);

            return false
        })

        window.hint = function (elem) {
            var hintElem = $(elem)
            var hintData = hintElem.data('ho-hintdata');
            hintElem.removeAttr('data-ho-hintdata')
            var hintType = hintElem.data('ho-hinttype');
            hintElem.removeAttr('data-ho-hinttype')

            if (hintType == 'knockout') {
                var knockoutData = ko.dataFor(elem);

                var script = $(`[data-requiremodule="${knockoutData.component}"]`)[0];
                if (script) {
                    var url = script.src;
                    url = url.slice(url.indexOf('pub/'));
                }

                hintData = {
                    info: [
                        knockoutData.name ? knockoutData.name : knockoutData.code,
                        knockoutData.component
                    ],
                    paths: {
                        'template': url
                    },
                    extra: {
                        'knockout': knockoutData
                    }
                }
            } else if (typeof hintData != 'object') {
                console.log('can not parse as json', hintData)
                return
            }

            let hintTypeStyle = {
                'block': 'background-color: hsl(195, 100%, 50%); padding:3px 6px; color:#fff; font-weight:bold;',
                'container': 'background-color: darkorange; padding:3px 6px; color:#fff; font-weight:bold;',
                'ui-component': 'background-color: hsl(269, 50%, 40%); padding:3px 6px; color:#fff; font-weight:bold;',
                'knockout': 'background-color: hsl(269, 50%, 40%); padding:3px 6px; color:#fff; font-weight:bold;',
            }

            console.groupCollapsed(
                '%c'+hintType + ': ' + _.values(hintData.info).join(' | '),
                hintTypeStyle[hintType]
            )

            _.each(hintData.paths, function(string, pathType) {
                pathType = pathType.charAt(0).toUpperCase() + pathType.slice(1);
                console.log(
                    `%c${pathType}`,
                    "font-weight:bold",
                    `http://localhost:63342/api/file/${string}`
                );
            });

            _.each(hintData.extra, function(string, type) {
                type = type.charAt(0).toUpperCase() + type.slice(1);
                console.log(
                    `%c${type}`,
                    "font-weight:bold",
                    string
                );
            });

            console.log(elem);

            console.groupEnd()
        }

        window.openTemplate = function (elem) {
            var hintElem = $(elem)
            var hintData = hintElem.data('ho-hintdata');
            hintElem.removeAttr('data-ho-hintdata')

            if (hintData['absolutePath']) {
                $.get('http://localhost:63342/api/file/' + hintData['absolutePath']);
            }
        }
    })
})
