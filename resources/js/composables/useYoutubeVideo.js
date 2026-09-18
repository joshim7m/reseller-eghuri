const YOUTUBE_ID_RE = /(?:youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/|embed\/|live\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;

/**
 * Extract a YouTube video ID from a URL string.
 * Supports: watch?v=, youtu.be, shorts, embed, live.
 *
 * @param {string|null} url
 * @returns {string|null}
 */
export function youtubeVideoId(url) {
    if (!url || typeof url !== 'string') {
        return null;
    }

    const match = url.match(YOUTUBE_ID_RE);

    return match ? match[1] : null;
}

/**
 * Build a privacy-enhanced embed URL from a video ID.
 *
 * @param {string} id
 * @returns {string}
 */
export function youtubeEmbedUrl(id) {
    return `https://www.youtube-nocookie.com/embed/${id}`;
}

let iframeApiPromise = null;

/**
 * Lazily load the YouTube IFrame API (window.YT) and resolve once ready.
 *
 * @returns {Promise<typeof window.YT>}
 */
export function loadYoutubeIframeApi() {
    if (window.YT?.Player) {
        return Promise.resolve(window.YT);
    }

    if (iframeApiPromise) {
        return iframeApiPromise;
    }

    iframeApiPromise = new Promise((resolve) => {
        const previous = window.onYouTubeIframeAPIReady;

        window.onYouTubeIframeAPIReady = () => {
            previous?.();
            resolve(window.YT);
        };

        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(tag);
    });

    return iframeApiPromise;
}
