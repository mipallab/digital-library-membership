// Book Reader Application (PDF.js Canvas Wrapper)
jQuery(document).ready(function($) {
    var bookId = $('body').data('book-id');
    var currentPage = parseInt($('body').data('start-page')) || 1;
    var watermarkText = $('body').data('watermark') || '';
    
    var pdfDoc = null;
    var pageRendering = false;
    var pageNumPending = null;
    var scale = 1.5; // Default crisp scale zoom
    var doublePageMode = window.innerWidth > 900;
    var totalPages = 0;
    var pageCache = {};
    var activeRenderTasks = {};
    var zoomFactor = 1.0;

    var activeTheme = 'light';
    var bookmarks = [];

    // Initialize PDF.js
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = dlmReaderParams.pdfWorkerUrl;
    } else {
        console.error('PDF.js library was not loaded.');
        return;
    }

    // Stream URL (Passing WP REST Nonce in query for secure cookie validation)
    var streamUrl = dlmReaderParams.apiUrl + '/book/' + bookId + '/stream?_wpnonce=' + dlmReaderParams.nonce;

    // 1. Fetch Book Details and Load Document
    showLoading(true, 'Connecting to secure stream...');
    
    $.ajax({
        url: dlmReaderParams.apiUrl + '/book/' + bookId + '/details',
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', dlmReaderParams.nonce);
        },
        success: function(book) {
            $('.dlm-book-title-lbl').text(book.title);
            logAnalyticsEvent('open');
            loadDocument();
        },
        error: function(err) {
            showLoading(false);
            alert('Failed to authorize reader connection.');
            window.location.href = '/library/';
        }
    });

    function loadDocument() {
        showLoading(true, 'Initializing PDF.js secure canvas...');
        
        pdfjsLib.getDocument({
            url: streamUrl,
            withCredentials: true,
            httpHeaders: {
                'X-WP-Nonce': dlmReaderParams.nonce
            }
        }).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            totalPages = pdfDoc.numPages;
            $('#total-page-num').text(totalPages);
            $('#dlm-page-slider').attr('max', totalPages);
            
            showLoading(false);
            
            // Adjust to double page spread if wide screen
            adjustLayout();
            renderPages(currentPage);
            
            // Extract outlines / Table of contents if available
            loadTableOfContents();
            
            // Load user bookmarks from localStorage (mock frontend db bookmark)
            loadBookmarks();
        }).catch(function(err) {
            console.error('Error loading PDF: ', err);
            showLoading(false);
            alert('Error rendering document. Check server permissions.');
        });
    }

    // Adjust single or double spread on resize
    function adjustLayout() {
        doublePageMode = window.innerWidth > 900 && totalPages > 1;
        if (doublePageMode) {
            $('#page-right').show();
            $('#dlm-flipbook').addClass('double-spread');
        } else {
            $('#page-right').hide();
            $('#dlm-flipbook').removeClass('double-spread');
        }
    }

    $(window).resize(function() {
        var oldMode = doublePageMode;
        adjustLayout();
        if (oldMode !== doublePageMode) {
            renderPages(currentPage);
        }
    });

    // Render Canvas Pages
    function renderPages(num) {
        if (num < 1) num = 1;
        if (num > totalPages) num = totalPages;
        
        currentPage = num;
        $('#current-page-num').text(currentPage);
        $('#dlm-page-slider').val(currentPage);

        // Left / Main Canvas
        renderSinglePage(currentPage, 'canvas-left');

        // Right Canvas if Double Spread
        if (doublePageMode && currentPage + 1 <= totalPages) {
            $('#page-right').css('visibility', 'visible');
            renderSinglePage(currentPage + 1, 'canvas-right');
        } else if (doublePageMode) {
            // Hide right canvas if past last page
            $('#page-right').css('visibility', 'hidden');
        }

        // Prefetch adjacent pages
        prefetchPage(currentPage - 1);
        prefetchPage(currentPage + 1);
        if (doublePageMode) {
            prefetchPage(currentPage + 2);
            prefetchPage(currentPage + 3);
        }

        // Save progress occasionally
        saveProgress();
    }

    function renderSinglePage(pageNum, canvasId) {
        if (pageNum < 1 || pageNum > totalPages) return;

        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');

        // Cancel existing active rendering task on this canvas to prevent collisions
        if (activeRenderTasks[canvasId]) {
            activeRenderTasks[canvasId].cancel();
            activeRenderTasks[canvasId] = null;
        }

        pageRendering = true;

        // Fetch page object
        getPageObj(pageNum).then(function(page) {
            var viewport = page.getViewport({ scale: scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            var renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            var renderTask = page.render(renderContext);
            activeRenderTasks[canvasId] = renderTask;

            renderTask.promise.then(function() {
                activeRenderTasks[canvasId] = null;
                pageRendering = false;
                // Apply dynamic watermark overlay on the parent container
                applyWatermark(canvasId);
            }).catch(function(err) {
                if (err.name !== 'RenderingCancelledException') {
                    console.error('Render error:', err);
                    pageRendering = false;
                }
            });
        });
    }

    // Get page promise (with local caching)
    function getPageObj(num) {
        if (pageCache[num]) {
            return Promise.resolve(pageCache[num]);
        }
        return pdfDoc.getPage(num).then(function(page) {
            pageCache[num] = page;
            return page;
        });
    }

    // Prefetch pages in background
    function prefetchPage(num) {
        if (num < 1 || num > totalPages || pageCache[num]) return;
        pdfDoc.getPage(num).then(function(page) {
            pageCache[num] = page;
        });
    }

    // Generate dynamic diagonal watermark SVG
    function applyWatermark(canvasId) {
        var wrapper = $('#' + canvasId).parent();
        var overlay = wrapper.find('.dlm-watermark-overlay');
        
        // Build repeating SVG background for the overlay
        var svgText = encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">' +
            '<text x="20" y="100" fill="rgba(128,128,128,0.12)" font-size="10" font-family="sans-serif" transform="rotate(-30 20 100)">' +
            watermarkText +
            '</text>' +
            '</svg>'
        );
        
        overlay.css('background-image', 'url("data:image/svg+xml,' + svgText + '")');
    }

    // Table of contents parsing
    function loadTableOfContents() {
        pdfDoc.getOutline().then(function(outline) {
            var $list = $('#dlm-toc-list');
            $list.empty();

            if (!outline || outline.length === 0) {
                $list.append('<li><span style="color:#888;">No table of contents embedded in this document.</span></li>');
                return;
            }

            outline.forEach(function(item) {
                var $li = $('<li></li>');
                var $a = $('<a href="#"></a>').text(item.title);
                
                $a.click(function(e) {
                    e.preventDefault();
                    // Resolve destination page if possible
                    if (item.dest) {
                        pdfDoc.getDestination(item.dest).then(function(destRef) {
                            pdfDoc.getPageIndex(destRef[0]).then(function(idx) {
                                renderPages(idx + 1);
                                logAnalyticsEvent('page_view', idx + 1);
                            });
                        });
                    }
                });

                $li.append($a);
                $list.append($li);
            });
        });
    }

    // Bookmarks management
    function loadBookmarks() {
        var saved = localStorage.getItem('dlm_bookmarks_' + bookId);
        if (saved) {
            bookmarks = JSON.parse(saved);
        }
        renderBookmarksList();
    }

    function renderBookmarksList() {
        var $list = $('#dlm-bookmarks-list');
        $list.empty();

        if (bookmarks.length === 0) {
            $list.append('<li class="empty"><span style="color:#888;">No bookmarks added.</span></li>');
            return;
        }

        // Sort bookmarks numerically
        bookmarks.sort(function(a, b) { return a - b; });

        bookmarks.forEach(function(p) {
            var $li = $('<li></li>');
            var $a = $('<a href="#">Page ' + p + '</a>');
            var $del = $('<span class="del-bookmark" style="color:#f44336; margin-left:10px; cursor:pointer;">&times;</span>');

            $a.click(function(e) {
                e.preventDefault();
                renderPages(p);
            });

            $del.click(function(e) {
                e.stopPropagation();
                bookmarks = bookmarks.filter(function(item) { return item !== p; });
                localStorage.setItem('dlm_bookmarks_' + bookId, JSON.stringify(bookmarks));
                renderBookmarksList();
            });

            $li.append($a).append($del);
            $list.append($li);
        });
    }

    $('#dlm-add-bookmark').click(function(e) {
        e.preventDefault();
        if (!bookmarks.includes(currentPage)) {
            bookmarks.push(currentPage);
            localStorage.setItem('dlm_bookmarks_' + bookId, JSON.stringify(bookmarks));
            renderBookmarksList();
        }
    });

    // 3D Flipping triggers & Nav arrows
    $('#dlm-next-page').click(function() {
        if (pageRendering) return;
        var step = doublePageMode ? 2 : 1;
        if (currentPage + step <= totalPages) {
            animateFlip('next', function() {
                renderPages(currentPage + step);
            });
        }
    });

    $('#dlm-prev-page').click(function() {
        if (pageRendering) return;
        var step = doublePageMode ? 2 : 1;
        if (currentPage - step >= 1) {
            animateFlip('prev', function() {
                renderPages(currentPage - step);
            });
        }
    });

    // Side navigation floating buttons
    $('#dlm-next-page-side').click(function(e) {
        e.preventDefault();
        $('#dlm-next-page').click();
    });

    $('#dlm-prev-page-side').click(function(e) {
        e.preventDefault();
        $('#dlm-prev-page').click();
    });

    // Premium 3D page flip transition using CSS transforms
    function animateFlip(direction, callback) {
        var $book = $('#dlm-flipbook');
        $book.addClass('flipping-' + direction);
        
        setTimeout(function() {
            callback();
            $book.removeClass('flipping-next flipping-prev');
        }, 350); // Matches CSS transition time
    }

    // Page slider inputs
    $('#dlm-page-slider').on('change input', function() {
        var val = parseInt($(this).val());
        renderPages(val);
    });

    // Zoom Controls
    // Zoom Controls via hardware-accelerated CSS transforms
    $('#dlm-zoom-in').click(function() {
        if (zoomFactor < 2.5) {
            zoomFactor += 0.25;
            applyZoom();
        }
    });

    $('#dlm-zoom-out').click(function() {
        if (zoomFactor > 0.5) {
            zoomFactor -= 0.25;
            applyZoom();
        }
    });

    function applyZoom() {
        var $viewport = $('.dlm-reader-viewport');
        var $book = $('#dlm-book-container');
        
        $book.css({
            'transform': 'scale(' + zoomFactor + ')',
            'transform-origin': 'center center',
            'transition': 'transform 0.2s ease-in-out'
        });

        if (zoomFactor > 1.0) {
            $viewport.css('overflow', 'auto');
        } else {
            $viewport.css('overflow', 'hidden');
        }
    }

    // Theme selector click
    $('#dlm-theme-btn').click(function() {
        var $body = $('body');
        if ($body.hasClass('theme-light')) {
            $body.removeClass('theme-light').addClass('theme-sepia');
            $(this).text('🪵');
        } else if ($body.hasClass('theme-sepia')) {
            $body.removeClass('theme-sepia').addClass('theme-dark');
            $(this).text('🌙');
        } else {
            $body.removeClass('theme-dark').addClass('theme-light');
            $(this).text('☀️');
        }
    });

    // Sidebar panel toggle
    $('#dlm-sidebar-toggle').click(function() {
        $('#dlm-sidebar').toggle();
    });

    $('.dlm-tab-btn').click(function() {
        var tab = $(this).data('tab');
        $('.dlm-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.dlm-sidebar-pane').hide();
        $('#pane-' + tab).show();
    });

    // Loading overlay wrapper
    function showLoading(show, message) {
        var $overlay = $('#dlm-loading-overlay');
        if (show) {
            $overlay.find('.dlm-loader-text').text(message || 'Loading...');
            $overlay.css('display', 'flex').hide().fadeIn();
        } else {
            $overlay.fadeOut();
        }
    }

    // Bookmark saving API helper
    function saveProgress() {
        var percent = Math.round((currentPage / totalPages) * 100);
        $.ajax({
            url: dlmReaderParams.apiUrl + '/book/' + bookId + '/progress',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', dlmReaderParams.nonce);
            },
            data: JSON.stringify({
                page: currentPage,
                percent: percent
            })
        });
    }

    // Analytics logging API helper
    function logAnalyticsEvent(event, pageNum) {
        $.ajax({
            url: dlmReaderParams.apiUrl + '/book/' + bookId + '/analytics',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', dlmReaderParams.nonce);
            },
            data: JSON.stringify({
                event: event,
                page: pageNum || currentPage
            })
        });
    }

    // Keyboard controls
    $(document).keydown(function(e) {
        if (e.keyCode === 37) { // Left arrow
            $('#dlm-prev-page').click();
        } else if (e.keyCode === 39) { // Right arrow
            $('#dlm-next-page').click();
        }
    });

    // Log close event on tab close / reload
    window.addEventListener('beforeunload', function() {
        logAnalyticsEvent('close');
    });

    // ------------------------------------------------------------------------
    // ANTI-EXTRACTION DRM HARDENING
    // ------------------------------------------------------------------------
    
    // 1. Disable right click
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    // 2. Disable text selection/copying
    document.addEventListener('selectstart', function(e) {
        e.preventDefault();
    });

    // 3. Disable keyboard shortcuts (Ctrl+C, Ctrl+S, Ctrl+P, F12, Ctrl+Shift+I)
    document.addEventListener('keydown', function(e) {
        // Ctrl+S
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
            e.preventDefault();
        }
        // Ctrl+P
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 80) {
            e.preventDefault();
            alert('Printing is disabled inside this secure reader container.');
        }
        // Ctrl+C
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 67) {
            e.preventDefault();
        }
        // F12
        if (e.keyCode === 123) {
            e.preventDefault();
        }
        // Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+U
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) {
            e.preventDefault();
        }
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 85) {
            e.preventDefault();
        }
    });

    // 4. Click edges of the shield overlay to turn pages
    $('.dlm-reader-shield').click(function(e) {
        var width = $(this).width();
        var clickX = e.pageX - $(this).offset().left;
        
        if (clickX < width * 0.35) {
            // Clicked left 35%
            $('#dlm-prev-page').click();
        } else if (clickX > width * 0.65) {
            // Clicked right 35%
            $('#dlm-next-page').click();
        }
    });

    // 5. Mobile Drag/Swipe triggers on the shield overlay
    var touchStartX = 0;
    var touchEndX = 0;

    $('.dlm-reader-shield').on('touchstart', function(e) {
        if (e.originalEvent && e.originalEvent.touches) {
            touchStartX = e.originalEvent.touches[0].screenX;
        } else if (e.changedTouches) {
            touchStartX = e.changedTouches[0].screenX;
        }
    });

    $('.dlm-reader-shield').on('touchend', function(e) {
        if (e.originalEvent && e.originalEvent.changedTouches) {
            touchEndX = e.originalEvent.changedTouches[0].screenX;
        } else if (e.changedTouches) {
            touchEndX = e.changedTouches[0].screenX;
        }
        handleSwipe();
    });

    function handleSwipe() {
        var diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 55) { // threshold
            if (diff > 0) {
                // Swiped Left -> next page
                $('#dlm-next-page').click();
            } else {
                // Swiped Right -> prev page
                $('#dlm-prev-page').click();
            }
        }
    }
});
