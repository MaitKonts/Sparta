<?php
function meiko_radio_upload_form() {
    echo '<div class="wrap">';
    echo '<h1>Radio Upload</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="radio_file">Upload Audio File</label></th>';
    echo '<td><input type="file" name="radio_file" accept="audio/*" required class="regular-text"></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit"><button type="submit" name="upload_radio" class="button button-primary">Upload</button></p>';
    echo '</form>';

    if (isset($_POST['upload_radio']) && !empty($_FILES['radio_file'])) {
        $uploaded_file = $_FILES['radio_file'];
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/meiko-radio/';
        
        // Ensure the directory exists
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        
        $file_path = $upload_path . basename($uploaded_file['name']);
        
        if (move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
            echo '<div class="updated notice is-dismissible"><p>File uploaded successfully!</p></div>';
        } else {
            echo '<div class="error notice is-dismissible"><p>Error uploading file.</p></div>';
        }
    }

    echo '</div>';
}


function meiko_radio_player() {
    $upload_dir = wp_upload_dir();
    $radio_path = $upload_dir['baseurl'] . '/meiko-radio/';
    $radio_files = glob($upload_dir['basedir'] . '/meiko-radio/*.mp3'); // Fetch only `.mp3` files
    
    if (!empty($radio_files)) {
        echo '<audio id="meiko-radio" controls>';
        foreach ($radio_files as $file) {
            $filename = basename($file);
            $file_url = $radio_path . $filename;
            echo '<source src="' . esc_url($file_url) . '" type="audio/mpeg">';
        }
        echo 'Your browser does not support the audio element.';
        echo '</audio>';
    } else {
        echo '<p>No audio files available.</p>';
    }
}

function meiko_radio_persistent_with_volume_shortcode() {
    $upload_dir = wp_upload_dir();
    $radio_path = $upload_dir['baseurl'] . '/meiko-radio/';
    $radio_files = glob($upload_dir['basedir'] . '/meiko-radio/*.mp3');

    if (!empty($radio_files)) {
        // Generate playlist
        $playlist = array_map(function ($file) use ($radio_path) {
            return esc_url($radio_path . basename($file));
        }, $radio_files);

        // Convert playlist to JavaScript array
        $playlist_js = json_encode($playlist);

        $html = '<div class="custom-audio-player">';
        $html .= '<h4 class="audio-title">Meiko Radio</h4>';
        $html .= '<audio id="meiko-audio" preload="metadata">';
        $html .= '<source id="audio-source" src="' . $playlist[0] . '" type="audio/mpeg">';
        $html .= '</audio>';
        $html .= '<div class="controls">';
        $html .= '<button id="play-pause" class="play">▶️</button>';
        $html .= '<div class="progress-container">';
        $html .= '<div id="progress-bar"></div>';
        $html .= '</div>';
        $html .= '<span id="current-time">0:00</span> / <span id="total-time">0:00</span>';
        $html .= '<div class="volume-container">';
        $html .= '<button id="volume-btn">🎧</button>';
        $html .= '<input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.5">';
        $html .= '</div>'; // Close volume-container
        $html .= '</div>'; // Close controls
        $html .= '</div>'; // Close custom-audio-player

        // JavaScript for autoplay, buttons, and progress bar
        $html .= '<script>
            const audio = document.getElementById("meiko-audio");
            const playPauseBtn = document.getElementById("play-pause");
            const progressContainer = document.querySelector(".progress-container");
            const progressBar = document.getElementById("progress-bar");
            const currentTimeEl = document.getElementById("current-time");
            const totalTimeEl = document.getElementById("total-time");
            const volumeBtn = document.getElementById("volume-btn");
            const volumeSlider = document.getElementById("volume-slider");
            const playlist = ' . $playlist_js . ';
            let currentIndex = 0;

        // Format time in minutes:seconds
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ":" + (secs < 10 ? "0" + secs : secs);
        }

        // Load saved state on page load
        document.addEventListener("DOMContentLoaded", () => {
            const savedIndex = localStorage.getItem("meiko-radio-index");
            const savedTime = localStorage.getItem("meiko-radio-time");
            const savedVolume = localStorage.getItem("meiko-radio-volume");
            const autoplayAllowed = localStorage.getItem("meiko-radio-autoplay");

            currentIndex = savedIndex ? parseInt(savedIndex, 10) : 0;

            if (playlist[currentIndex]) {
                audio.src = playlist[currentIndex];
            }

            if (savedTime) {
                audio.currentTime = parseFloat(savedTime);
            }
            if (savedVolume) {
                audio.volume = parseFloat(savedVolume);
            } else {
                audio.volume = 0.5; // Default volume
            }

            if (autoplayAllowed === "true") {
                audio.play().catch(() => {
                    console.log("Autoplay blocked. User interaction required.");
                });
                playPauseBtn.textContent = "⏸️";
            }
        });

        // Save playback state (playing or paused)
        audio.addEventListener("play", () => {
            localStorage.setItem("meiko-radio-playing", "true"); // Save playing state
            playPauseBtn.textContent = "⏸️";
        });
        
        audio.addEventListener("pause", () => {
            localStorage.setItem("meiko-radio-playing", "false"); // Save paused state
            playPauseBtn.textContent = "▶️";
        });
        
        // Load saved state on page load
        document.addEventListener("DOMContentLoaded", () => {
            const savedIndex = localStorage.getItem("meiko-radio-index");
            const savedTime = localStorage.getItem("meiko-radio-time");
            const savedVolume = localStorage.getItem("meiko-radio-volume");
            const wasPlaying = localStorage.getItem("meiko-radio-playing") === "true"; // Retrieve play state
        
            currentIndex = savedIndex ? parseInt(savedIndex, 10) : 0;
        
            if (playlist[currentIndex]) {
                audio.src = playlist[currentIndex];
            }
        
            if (savedTime) {
                audio.currentTime = parseFloat(savedTime);
            }
        
            if (savedVolume) {
                audio.volume = parseFloat(savedVolume);
                volumeSlider.value = savedVolume; // Update the volume slider visually
            } else {
                audio.volume = 0.5; // Default volume
                volumeSlider.value = 0.5; // Default slider value
            }
        
            if (wasPlaying) {
                audio.play();
                playPauseBtn.textContent = "⏸️"; // Set correct icon
            } else {
                audio.pause();
                playPauseBtn.textContent = "▶️"; // Set correct icon
            }
        });
        
        // Save playback state (playing or paused) and update icon
        audio.addEventListener("play", () => {
            localStorage.setItem("meiko-radio-playing", "true");
            playPauseBtn.textContent = "⏸️"; // Update icon
        });
        
        audio.addEventListener("pause", () => {
            localStorage.setItem("meiko-radio-playing", "false");
            playPauseBtn.textContent = "▶️"; // Update icon
        });
        
        // Save volume when changed
        volumeSlider.addEventListener("input", () => {
            audio.volume = volumeSlider.value;
            localStorage.setItem("meiko-radio-volume", volumeSlider.value); // Save volume level
        });


        window.addEventListener("beforeunload", () => {
            localStorage.setItem("meiko-radio-index", currentIndex);
            localStorage.setItem("meiko-radio-time", audio.currentTime);
            localStorage.setItem("meiko-radio-volume", audio.volume);
        });

        // Play/Pause button functionality
        playPauseBtn.addEventListener("click", () => {
            if (audio.paused) {
                audio.play();
            } else {
                audio.pause();
            }
        });

        // Update progress bar and time
        audio.addEventListener("timeupdate", () => {
            const progressPercent = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = progressPercent + "%";
            currentTimeEl.textContent = formatTime(audio.currentTime);
        });

        // Set total duration
        audio.addEventListener("loadedmetadata", () => {
            totalTimeEl.textContent = formatTime(audio.duration);
        });

        // Seek functionality
        progressContainer.addEventListener("click", (e) => {
            const width = progressContainer.clientWidth;
            const clickX = e.offsetX;
            const duration = audio.duration;
            audio.currentTime = (clickX / width) * duration;
        });

        // Volume control
        volumeSlider.addEventListener("input", () => {
            audio.volume = volumeSlider.value;
        });

        // Toggle mute
        volumeBtn.addEventListener("click", () => {
            if (audio.muted) {
                audio.muted = false;
                volumeBtn.textContent = "🎧";
            } else {
                audio.muted = true;
                volumeBtn.textContent = "🔇";
            }
        });

        // Play next track when the current one ends
        audio.addEventListener("ended", () => {
            currentIndex = (currentIndex + 1) % playlist.length; // Loop back to the first track if at the end
            audio.src = playlist[currentIndex];
            audio.load(); // Load the new track
            audio.play(); // Start playing
            localStorage.setItem("meiko-radio-index", currentIndex); // Save the new track index
        });
        </script>';

        return $html;
    } else {
        return '<p>No audio files available for the radio.</p>';
    }
}