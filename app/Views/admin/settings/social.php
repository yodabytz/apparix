<?php
/**
 * Social Media Links Settings
 */
?>
<div class="page-header">
    <h1>Social Media Links</h1>
    <a href="/admin/settings" class="btn btn-outline">Back to Settings</a>
</div>

<form id="socialForm" method="POST">
    <?php echo csrfField(); ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Social Media Profiles</h3>
        </div>

        <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 1.5rem;">
            Add your social media profile URLs below. Leave blank to hide from the footer.
        </p>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook
                    </span>
                </label>
                <input type="url" name="social_facebook" class="form-input" value="<?php echo escape($settings['social_facebook']); ?>" placeholder="https://facebook.com/yourpage">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        Instagram
                    </span>
                </label>
                <input type="url" name="social_instagram" class="form-input" value="<?php echo escape($settings['social_instagram']); ?>" placeholder="https://instagram.com/yourprofile">
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X (Twitter)
                    </span>
                </label>
                <input type="url" name="social_twitter" class="form-input" value="<?php echo escape($settings['social_twitter']); ?>" placeholder="https://x.com/yourprofile">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        TikTok
                    </span>
                </label>
                <input type="url" name="social_tiktok" class="form-input" value="<?php echo escape($settings['social_tiktok']); ?>" placeholder="https://tiktok.com/@yourprofile">
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        YouTube
                    </span>
                </label>
                <input type="url" name="social_youtube" class="form-input" value="<?php echo escape($settings['social_youtube']); ?>" placeholder="https://youtube.com/@yourchannel">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0a12 12 0 0 0-4.373 23.178c-.07-.634-.134-1.606.028-2.298.146-.625.938-3.977.938-3.977s-.239-.479-.239-1.187c0-1.113.645-1.943 1.448-1.943.682 0 1.012.512 1.012 1.127 0 .687-.437 1.712-.663 2.663-.188.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.664 0-1.915-1.377-3.254-3.342-3.254-2.276 0-3.612 1.707-3.612 3.471 0 .688.265 1.425.595 1.826a.24.24 0 0 1 .056.23c-.061.252-.196.796-.222.907-.035.146-.116.177-.268.107-1-.465-1.624-1.926-1.624-3.1 0-2.523 1.834-4.84 5.286-4.84 2.775 0 4.932 1.977 4.932 4.62 0 2.757-1.739 4.976-4.151 4.976-.811 0-1.573-.421-1.834-.919l-.498 1.902c-.181.695-.669 1.566-.995 2.097A12 12 0 1 0 12 0z"/></svg>
                        Pinterest
                    </span>
                </label>
                <input type="url" name="social_pinterest" class="form-input" value="<?php echo escape($settings['social_pinterest']); ?>" placeholder="https://pinterest.com/yourprofile">
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        LinkedIn
                    </span>
                </label>
                <input type="url" name="social_linkedin" class="form-input" value="<?php echo escape($settings['social_linkedin']); ?>" placeholder="https://linkedin.com/company/yourcompany">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.374 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0 1 12 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.626-5.374-12-12-12z"/></svg>
                        Threads
                    </span>
                </label>
                <input type="url" name="social_threads" class="form-input" value="<?php echo escape($settings['social_threads']); ?>" placeholder="https://threads.net/@yourprofile">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Marketplace & Community</h3>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 17.703c-.187.093-.389.153-.601.153-.766 0-1.379-.613-1.379-1.379s.613-1.379 1.379-1.379c.212 0 .414.06.601.153.187-.093.389-.153.601-.153.766 0 1.379.613 1.379 1.379s-.613 1.379-1.379 1.379c-.212 0-.414-.06-.601-.153zM12 3.803c2.136 0 3.923.746 5.167 2.085C18.301 7.038 19 8.827 19 11c0 1.393-.327 2.646-.938 3.596-.578.897-1.341 1.476-2.062 1.476-.318 0-.542-.085-.743-.241-.188-.146-.371-.363-.583-.663l-.252-.359c-.228-.324-.419-.476-.671-.476-.261 0-.446.152-.671.476l-.252.359c-.212.3-.395.517-.583.663-.201.156-.425.241-.743.241-.721 0-1.484-.579-2.062-1.476C9.327 13.646 9 12.393 9 11c0-2.173.699-3.962 1.833-5.112C12.077 4.549 13.864 3.803 12 3.803z"/></svg>
                        Etsy Shop
                    </span>
                </label>
                <input type="url" name="social_etsy" class="form-input" value="<?php echo escape($settings['social_etsy']); ?>" placeholder="https://etsy.com/shop/yourshop">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M.045 18.02c.072-.116.187-.124.348-.022 3.636 2.11 7.594 3.166 11.87 3.166 2.852 0 5.668-.533 8.447-1.595l.315-.118c.138-.045.269-.015.394.09.108.105.161.232.161.38a.527.527 0 0 1-.157.378c-.073.09-.195.161-.371.212a23.98 23.98 0 0 1-4.466 1.078 24.537 24.537 0 0 1-4.553.425c-2.89 0-5.692-.605-8.406-1.812a24.2 24.2 0 0 1-3.558-1.912c-.07-.04-.118-.085-.146-.134a.35.35 0 0 1-.037-.2.358.358 0 0 1 .159-.936zm8.198-10.37c0-1.335.304-2.387.917-3.15.587-.747 1.293-1.118 2.117-1.118.825 0 1.513.363 2.073 1.087.56.723.836 1.792.836 3.188v1.12c0 .05-.016.095-.05.13a.184.184 0 0 1-.137.056h-5.484a.178.178 0 0 1-.12-.056.165.165 0 0 1-.05-.12v-1.137zm6.953 1.68c.133 0 .244-.05.331-.152.087-.1.13-.22.13-.36V7.683c0-1.5-.312-2.75-.936-3.75-.624-1-1.473-1.715-2.546-2.145C11.09 1.262 9.846 1 8.433 1 6.948 1 5.602 1.3 4.391 1.902c-.192.098-.25.244-.17.44a.395.395 0 0 0 .37.212c.065 0 .147-.015.247-.044a10.12 10.12 0 0 1 3.026-.452c1.39 0 2.577.315 3.557.946.98.631 1.47 1.723 1.47 3.278v.64a.178.178 0 0 1-.057.138.178.178 0 0 1-.132.056H6.186a.185.185 0 0 1-.138-.056.186.186 0 0 1-.05-.138v-.478c0-.95.226-1.753.68-2.402.454-.65 1.116-1.016 1.987-1.096.131-.008.21-.054.236-.139.025-.085-.01-.167-.108-.248a.367.367 0 0 0-.236-.083 3.69 3.69 0 0 0-2.584.984c-.73.676-1.095 1.587-1.095 2.733v.578c0 .05-.02.097-.057.138a.184.184 0 0 1-.137.056H3.254a.178.178 0 0 1-.12-.056.165.165 0 0 1-.05-.138v-.478c0-1.127.27-2.055.812-2.783.542-.729 1.288-1.108 2.24-1.141.131-.008.206-.057.226-.147s-.024-.167-.13-.231a.455.455 0 0 0-.25-.065c-1.09 0-1.98.42-2.668 1.26C2.625 4.64 2.286 5.742 2.286 7.1v.653c0 .05-.016.095-.05.13a.185.185 0 0 1-.137.056H.78a.178.178 0 0 1-.12-.056.165.165 0 0 1-.05-.13V6.69c0-1.548.413-2.87 1.24-3.966.826-1.096 1.918-1.787 3.278-2.074a.457.457 0 0 0 .315-.209c.065-.098.059-.183-.017-.255a.438.438 0 0 0-.315-.095C3.478.24 2.102.893 1.032 2.2-.035 3.507-.57 5.035-.57 6.786v.868c0 .098.037.18.113.248a.36.36 0 0 0 .247.101h1.296c.099 0 .183-.033.253-.101a.326.326 0 0 0 .108-.248v-.868c0-1.105.289-2.05.868-2.838.58-.787 1.35-1.252 2.312-1.393.131-.016.206-.065.224-.147a.215.215 0 0 0-.066-.212.375.375 0 0 0-.25-.084c-1.155.148-2.103.68-2.843 1.597-.74.917-1.112 2.002-1.112 3.255v.65c0 .098.034.18.1.247a.326.326 0 0 0 .24.101h1.313c.098 0 .18-.033.247-.101a.336.336 0 0 0 .1-.247v-.65c0-.75.167-1.4.501-1.953.334-.553.8-.888 1.396-1.004.131-.025.201-.078.21-.16.01-.08-.032-.152-.125-.214a.437.437 0 0 0-.242-.074c-.75.115-1.367.47-1.851 1.064-.484.594-.726 1.302-.726 2.124v.67c0 .098.033.178.1.24a.326.326 0 0 0 .24.092h7.11c.097 0 .18-.033.247-.101a.336.336 0 0 0 .1-.247v-.553c0-.816.23-1.535.69-2.156.46-.62 1.073-.966 1.838-1.037.131-.008.201-.057.212-.147.01-.09-.028-.16-.113-.212a.534.534 0 0 0-.268-.066c-.88.082-1.583.435-2.107 1.06-.525.625-.787 1.38-.787 2.267v.64c0 .05-.017.095-.05.138a.183.183 0 0 1-.138.056H9.65a.178.178 0 0 1-.132-.056.183.183 0 0 1-.056-.138V7.57c0-1.105.283-2.055.85-2.85.567-.797 1.331-1.212 2.29-1.246.132-.008.206-.054.225-.138.018-.084-.024-.16-.125-.226a.464.464 0 0 0-.258-.066c-1.122.033-2.025.472-2.71 1.317-.684.846-1.026 1.865-1.026 3.06v.497c0 .05-.016.095-.05.138a.183.183 0 0 1-.137.056H7.224a.178.178 0 0 1-.132-.056.183.183 0 0 1-.057-.138v-.447c0-1.178.262-2.187.785-3.027.523-.84 1.24-1.314 2.15-1.422.131-.017.207-.066.226-.147.018-.082-.024-.152-.125-.213a.446.446 0 0 0-.258-.074c-1.04.115-1.876.583-2.51 1.402-.632.82-.948 1.807-.948 2.961v.967c0 .098.033.178.1.24a.326.326 0 0 0 .24.092h6.7z"/></svg>
                        Amazon Store
                    </span>
                </label>
                <input type="url" name="social_amazon" class="form-input" value="<?php echo escape($settings['social_amazon']); ?>" placeholder="https://amazon.com/stores/yourstore">
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">
                    <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z"/></svg>
                        Discord
                    </span>
                </label>
                <input type="url" name="social_discord" class="form-input" value="<?php echo escape($settings['social_discord']); ?>" placeholder="https://discord.gg/yourserver">
            </div>

            <div class="form-group">
                <!-- Empty for alignment -->
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 1rem; align-items: center;">
        <button type="submit" class="btn btn-primary" id="saveBtn">Save Social Links</button>
        <span id="saveStatus" style="color: var(--admin-success); font-size: 0.875rem;"></span>
    </div>
</form>

<script>
document.getElementById('socialForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var btn = document.getElementById('saveBtn');
    var status = document.getElementById('saveStatus');

    btn.disabled = true;
    btn.textContent = 'Saving...';
    status.textContent = '';

    var formData = new FormData(this);

    fetch('/admin/settings/social/update', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            status.style.color = 'var(--admin-success)';
            status.textContent = 'Saved!';
        } else {
            status.style.color = 'var(--admin-danger)';
            status.textContent = data.error || 'Failed to save';
        }
    })
    .catch(function(err) {
        status.style.color = 'var(--admin-danger)';
        status.textContent = 'Error saving settings';
    })
    .finally(function() {
        btn.disabled = false;
        btn.textContent = 'Save Social Links';
        setTimeout(function() { status.textContent = ''; }, 3000);
    });
});
</script>
