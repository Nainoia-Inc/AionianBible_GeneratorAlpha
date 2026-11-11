<?php
// Aionian Bible Flipbook with Dearflip
// https://github.com/ibra-kdbra/Zaya
if (empty($_GET['pdf']) ||
	!is_file('.'.$_GET['pdf']) ||
	!preg_match('#^/resources/Holy-Bible---(.+)---(Aionian|Source)-Edition\.pdf$#', $_GET['pdf'], $match)) {
	exit(header('Location: /zaya-not-found/',true,302));
}
$ab_icon = '/images/Aionian-Bible-Online.png';
$ab_link = '/Bibles/'.$match[1];

/* Zaya */
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Zaya Flipbook at AionianBible.org<? echo $ab_link; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/svg+xml" href="/assets/zaya.svg">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="lib/css/style.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Custom PDF Configuration -->
  <!-- Uncomment and set your own PDF URL to change the default document: -->
  <!-- <script>window.ZAYA_DEFAULT_PDF = "./path/to/your.pdf";</script> -->

</head>

<body class="bg-gray-900 text-white">

<h1 style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;">
Zaya Flipbook at AionianBible.org<? echo $ab_link; ?>
</h1>

    <!-- GitHub Link 
    <div class="fixed top-4 right-4 z-50">
        <a href="https://github.com/ibra-kdbra/Zaya" target="_blank" class="flex items-center px-3 py-3 bg-[var(--bg-tertiary)]
    text-[var(--text-primary)] border border-[var(--border-primary)] rounded-lg shadow-md transition duration-300 hover:bg-[var(--bg-accent-hover)] hover:text-[var(--text-accent)]" title="GitHub Repository">
            <i class="fab fa-github text-lg" aria-hidden="true"></i>
        </a>
    </div> -->

    <!-- AB Link instead -->
    <div class="fixed top-4 right-4 z-50">
        <a href="<? echo $ab_link; ?>" title="Return to Aionian Bible" style="padding: 0.25rem">
            <img src="<? echo $ab_icon; ?>" alt="Return to Aionian Bible">
        </a>
    </div>

    <!-- Unified Control Panel Toggle Button -->
    <div class="fixed top-20 right-4 z-50">
        <button id="toggleUnifiedPanelBtn" class="px-4 py-2 bg-[var(--bg-tertiary)] text-[var(--text-primary)] border border-[var(--border-primary)]
    rounded-lg shadow-md transition duration-300 hover:bg-[var(--bg-accent-hover)] hover:text-[var(--text-accent)]" title="Toggle Control Panel" aria-label="Toggle Control Panel">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Version & Changelog Link -->
    <div class="fixed bottom-4 left-4 z-50">
        <a href="changelog.html" class="flex items-center px-4 py-2 bg-[var(--bg-tertiary)] text-[var(--text-primary)] border border-[var(--border-primary)]
    rounded-lg shadow-md transition duration-300 hover:bg-[var(--bg-accent-hover)] hover:text-[var(--text-accent)] no-underline" title="View Changelog">
            <i class="fas fa-code-branch text-blue-400 mr-2" aria-hidden="true"></i>
            <span id="currentVersion" class="font-mono text-sm" data-original="Loading...">Loading...</span>
        </a>
    </div>

  <!-- Unified Right-Side Control Panel -->
  <div id="unifiedPanel" class="panel-container">
    <div class="panel-header">
      <h2 class="flex items-center gap-2">
        <i class="fas fa-sliders-h text-blue-400" aria-hidden="true"></i>
        Control Panel
      </h2>
      <button id="closeUnifiedPanelBtn" class="panel-close" aria-label="Close control panel">✕</button>
    </div>

    <!-- Settings Section -->
    <div class="panel-section">
      <h3 class="panel-section-title">
        <i class="fas fa-cog" aria-hidden="true"></i>
        SETTINGS
      </h3>
      <div class="settings-group-modern">
        <!-- Appearance Select -->
        <button id="openThemeSelectorBtn" class="setting-item-clickable">
          <span class="item-left">
            <i class="fas fa-palette" aria-hidden="true"></i>
            <span>Theme: Default</span>
          </span>
          <i class="fas fa-chevron-right chevron" aria-hidden="true"></i>
        </button>

        <div class="h-px bg-gray-800 my-2 opacity-30"></div>

        <!-- Bottom Panel Toggle -->
        <div class="setting-row-modern">
          <span class="setting-label-modern">Bottom Panel</span>
          <div class="control-group">
            <span class="control-label">Auto-hide</span>
            <label class="toggle-switch-modern">
              <input type="checkbox" id="bottomPanelAlwaysShown" aria-label="Always show bottom panel">
              <span class="slider-modern"></span>
            </label>
            <span class="control-label">Fixed</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quotes Section -->
    <div class="panel-section">
      <h3 class="panel-section-title">
        <i class="fas fa-quote-left" aria-hidden="true"></i>
        QUOTES
      </h3>
      <div class="quotes-tools">
        <button id="quotesToggleBtn" class="panel-button" title="Toggle Quotes" aria-label="Toggle Quotes">
          <i class="fas fa-quote-left" aria-hidden="true"></i>
        </button>
        <button id="exportQuotesBtn" class="panel-button" title="Export Quotes" aria-label="Export Quotes">
          <i class="fas fa-download" aria-hidden="true"></i>
        </button>
      </div>
      <div class="quotes-input-container">
        <input id="quoteInput" type="text" placeholder="Add new quote?" class="panel-input" aria-label="Quote text">
        <button id="addQuoteBtn" class="panel-button add-quote-btn" title="Add Quote" aria-label="Add Quote">
          <i class="fas fa-plus" aria-hidden="true"></i>
        </button>
      </div>
      <div class="quotes-list" id="quoteList">
        <!-- Quotes will be dynamically added here -->
      </div>
    </div>


    <!-- PDF Options Section -->
    <div class="panel-section">
      <h3 class="panel-section-title">
        <i class="fas fa-file-pdf" aria-hidden="true"></i>
        PDF OPTIONS
      </h3>
      <div class="pdf-tools">
        <div class="tool-group">
          <span class="tool-label">Reading Direction</span>
          <button id="toggleDirectionBtn" class="panel-button direction-btn" title="Toggle Reading Direction" aria-label="Toggle Reading Direction">
            <i class="fas fa-exchange-alt" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <div class="pdf-inputs">
        <div class="input-field">
          <input id="pdfUrl" type="url" placeholder="Paste PDF URL here..." class="panel-input" autocomplete="off" aria-label="PDF web address">
          <button id="loadPdfUrlBtn" class="panel-button load-btn" title="Load PDF from URL" aria-label="Load PDF from URL">
            <i class="fas fa-globe" aria-hidden="true"></i>
          </button>
          <button id="loadPdfFileBtn" class="panel-button load-btn" title="Load Local PDF" aria-label="Load Local PDF">
            <i class="fas fa-folder-open" aria-hidden="true"></i>
          </button>
          <input id="pdfFile" type="file" accept="application/pdf" style="display: none;">
        </div>
      </div>
    </div>



    <!-- Media Section -->
    <div class="panel-section">
      <h3 class="panel-section-title">
        <i class="fas fa-play-circle" aria-hidden="true"></i>
        MEDIA PLAYER
      </h3>

      <!-- Media Mode Switcher -->
      <div class="flex bg-gray-800/50 p-1 rounded-lg mb-4 border border-gray-700">
        <button id="switchYoutubeMode" class="flex-1 flex items-center justify-center gap-2 py-1.5 rounded-md transition-all duration-300 bg-blue-500 text-white shadow-lg">
          <i class="fab fa-youtube" aria-hidden="true"></i>
          <span class="text-xs font-medium">YouTube</span>
        </button>
        <button id="switchAudioMode" class="flex-1 flex items-center justify-center gap-2 py-1.5 rounded-md transition-all duration-300 text-gray-400 hover:text-white">
          <i class="fas fa-music" aria-hidden="true"></i>
          <span class="text-xs font-medium">Audio</span>
        </button>
      </div>

      <!-- YouTube Input Group -->
      <div id="youtubeInputGroup" class="youtube-input-container">
        <div class="input-field">
          <input id="youtubeUrl" type="url" placeholder="Paste YouTube URL here..." class="panel-input" autocomplete="off" aria-label="YouTube web address">
          <button id="loadYoutubeBtn" class="panel-button load-btn" title="Play YouTube Video" aria-label="Play YouTube Video">
            <i class="fas fa-play" aria-hidden="true"></i>
          </button>
          <button id="loadPlaylistBtn" class="panel-button load-btn" title="Load Playlist" aria-label="Load Playlist">
            <i class="fas fa-compact-disc" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <!-- Local Audio Input Group (Hidden by default) -->
      <div id="audioInputGroup" class="local-audio-container hidden">
        <div class="input-field">
          <input id="localAudioFile" type="file" accept="audio/*" class="sr-only peer">
          <label for="localAudioFile" class="peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400 panel-button w-full text-center cursor-pointer flex items-center justify-center gap-2 bg-[var(--bg-tertiary)] hover:bg-[var(--bg-accent-hover)] transition-colors py-2 rounded group">
            <i class="fas fa-cloud-upload-alt text-blue-400 group-hover:scale-110 transition-transform" aria-hidden="true"></i>
            <span>Import Local File</span>
          </label>
        </div>
        <div id="localAudioFileName" class="text-[10px] text-gray-500 mt-1 truncate hidden text-center italic"></div>
      </div>

      <div class="media-player-container mt-2 relative group">
        <!-- Unified Close Button (Top-Right) -->
        <button id="closeMediaContainer" class="absolute -top-2 -right-2 w-6 h-6 bg-gray-800 border border-gray-700 rounded-full flex items-center justify-center hover:bg-red-500 transition-colors z-10 hidden shadow-xl group-hover:flex" aria-label="Close media player">
          <i class="fas fa-times text-[10px] text-gray-400 hover:text-white" aria-hidden="true"></i>
        </button>

        <div class="media-player-youtube" id="youtubePlayerContainer">
          <iframe id="youtubePlayer" src="about:blank" frameborder="0" allowfullscreen title="Youtube Player"></iframe>
        </div>
        <!-- Custom Local Audio Player UI -->
        <div id="customAudioPlayer" class="hidden flex flex-col gap-3 mt-4 p-4 bg-gray-800/40 rounded-xl border border-gray-700/50 backdrop-blur-sm">
          <div class="flex items-center gap-4">
            <button id="audioPlayPauseBtn" class="w-10 h-10 flex items-center justify-center bg-blue-500 hover:bg-blue-600 rounded-full transition-all duration-300 shadow-lg shadow-blue-500/20 active:scale-95" aria-label="Play or pause">
              <i class="fas fa-play text-xs text-white" aria-hidden="true"></i>
            </button>
            <div class="flex-1 flex flex-col gap-1.5">
              <input type="range" id="audioProgressSlider" min="0" max="100" value="0" class="w-full h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-400" aria-label="Audio position">
              <div class="flex justify-between items-center px-0.5">
                <span id="audioCurrentTime" class="text-[10px] text-gray-500 font-mono">0:00</span>
                <span id="audioTotalTime" class="text-[10px] text-gray-500 font-mono">0:00</span>
              </div>
            </div>
          </div>

          <!-- Integrated Volume Slider & Loop Toggle -->
          <div class="flex flex-col gap-2 mt-1 pt-2 border-t border-gray-700/30">
            <div class="flex items-center gap-3 px-1">
              <i class="fas fa-volume-down text-[10px] text-gray-500" aria-hidden="true"></i>
              <input type="range" id="volumeSlider" min="0" max="100" value="50" class="flex-1 h-0.5 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-gray-400 hover:accent-blue-400 transition-all" aria-label="Audio volume">
              <i class="fas fa-volume-up text-[10px] text-gray-500" aria-hidden="true"></i>
            </div>

            <div class="flex items-center justify-between px-1 mt-1">
              <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Auto Repeat Audio</span>
              <label class="toggle-switch-modern scale-75 origin-right">
                <input type="checkbox" id="mediaLoopToggle" aria-label="Loop audio">
                <span class="slider-modern"></span>
              </label>
            </div>
          </div>

          <audio id="localAudioPlayer" class="hidden"></audio>
        </div>

        <!-- Volume & Loop for Video mode -->
        <div id="videoVolumeControl" class="media-buttons mt-2 hidden flex-col gap-2 p-2 bg-gray-800/40 rounded-xl border border-gray-700/50">
          <div class="volume-control flex items-center gap-3 px-1">
            <i class="fas fa-volume-up text-xs text-gray-500" aria-hidden="true"></i>
            <input type="range" id="videoVolumeSlider" min="0" max="100" value="50" class="volume-slider flex-1" aria-label="Video volume">
          </div>
          <div class="flex items-center justify-between px-1 mt-1 border-t border-gray-700/30 pt-2">
            <span class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Auto Repeat Video</span>
            <label class="toggle-switch-modern scale-75 origin-right">
              <input type="checkbox" id="videoMediaLoopToggle" aria-label="Loop video">
              <span class="slider-modern"></span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Custom Flipbook Control Bar -->
  <div class="hover-trigger" id="bottomHoverTrigger"></div>
  <div id="customControlBar" class="always-shown">
    <button class="custom-ui-btn" id="customPrevBtn" title="Previous Page" aria-label="Previous Page">
      <i class="fas fa-chevron-left" aria-hidden="true"></i>
    </button>

    <div class="page-indicator">
      <input type="text" id="customCurrentPageInput" class="page-input-custom" inputmode="numeric" aria-label="Page number"> / <span id="customTotalPages">1</span>
    </div>

    <button class="custom-ui-btn" id="customNextBtn" title="Next Page" aria-label="Next Page">
      <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>

    <div class="h-8 w-px bg-gray-700 mx-2"></div>

    <button class="custom-ui-btn" id="customOutlineBtn" title="Toggle Outline" aria-label="Toggle Outline">
      <i class="fas fa-list-ul" aria-hidden="true"></i>
    </button>

    <button class="custom-ui-btn" id="customThumbnailBtn" title="Toggle Thumbnails" aria-label="Toggle Thumbnails">
      <i class="fas fa-th-large" aria-hidden="true"></i>
    </button>

    <div class="h-8 w-px bg-gray-700 mx-2 mobile-hide"></div>

    <button class="custom-ui-btn mobile-hide" id="customZoomOutBtn" title="Zoom Out" aria-label="Zoom Out">
      <i class="fas fa-search-minus" aria-hidden="true"></i>
    </button>

    <button class="custom-ui-btn mobile-hide" id="customZoomInBtn" title="Zoom In" aria-label="Zoom In">
      <i class="fas fa-search-plus" aria-hidden="true"></i>
    </button>

    <div class="h-8 w-px bg-gray-700 mx-2 mobile-hide"></div>

    <button class="custom-ui-btn" id="customFullscreenBtn" title="Toggle Fullscreen" aria-label="Toggle Fullscreen">
      <i class="fas fa-expand" aria-hidden="true"></i>
    </button>

    <button class="custom-ui-btn mobile-hide" id="customShareBtn" title="Share" aria-label="Share">
      <i class="fas fa-share-alt" aria-hidden="true"></i>
    </button>

    <div class="relative">
      <button class="custom-ui-btn" id="customMoreBtn" title="More Options" aria-haspopup="true" aria-controls="customMoreMenu" aria-label="More Options">
        <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
      </button>

      <!-- More Menu Dropdown -->
      <div id="customMoreMenu" class="absolute bottom-full right-0 mb-4 w-56 bg-[#2D3748] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform translate-y-2 transition-all duration-300 z-[1001]">
        <div class="p-2 flex flex-col gap-1">
          <button class="more-menu-item" id="menuDownloadBtn">
            <i class="fas fa-download" aria-hidden="true"></i>
            <span>Download PDF</span>
          </button>
          <button class="more-menu-item" id="menuPageModeBtn">
            <i class="fas fa-file-alt" aria-hidden="true"></i>
            <span>Single Page Mode</span>
          </button>
          <button class="more-menu-item" id="menuFirstPageBtn">
            <i class="fas fa-angle-double-left" aria-hidden="true"></i>
            <span>Goto First Page</span>
          </button>
          <button class="more-menu-item" id="menuLastPageBtn">
            <i class="fas fa-angle-double-right" aria-hidden="true"></i>
            <span>Goto Last Page</span>
          </button>
          <div class="h-px bg-gray-700 my-1"></div>
          <button class="more-menu-item" id="menuSoundBtn">
            <i class="fas fa-volume-up" aria-hidden="true"></i>
            <span>Sound: On</span>
          </button>
        </div>
        <!-- Arrow -->
        <div class="absolute -bottom-2 right-4 w-4 height-4 bg-[#2D3748] border-r border-b border-gray-700 transform rotate-45"></div>
      </div>
    </div>
  </div>

  <!-- Container where the flipbook will be displayed -->
  <div id="flipbookContainer" class="h-screen w-full relative" role="main"></div>

  <!-- PDF-Specific Quotes Modal -->
  <div id="pdfSpecificQuotesModal" class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="flex items-center gap-2">
          <i class="fas fa-edit text-yellow-400" aria-hidden="true"></i>
          PDF Quotes
        </h2>
        <button class="modal-close" aria-label="Close PDF quotes">✕</button>
      </div>
      <div class="modal-body">
        <div id="modalQuoteList" class="modal-quote-list">
          <!-- PDF-specific quotes will be displayed here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Main Application Entry Point -->
  <script type="module" src="lib/js/app.js"></script>

</body>
</html>
