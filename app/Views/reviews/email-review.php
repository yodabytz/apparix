<div class="email-review-page">
    <div class="container">
        <div class="review-card-wrapper">
            <h1>Review Your Purchase</h1>

            <div class="product-to-review">
                <img src="<?php echo escape($product['primary_image'] ?? '/assets/images/placeholder.png'); ?>"
                     alt="<?php echo escape($product['name']); ?>"
                     class="product-review-image">
                <h2><?php echo escape($product['name']); ?></h2>
            </div>

            <form action="/review/submit" method="POST" class="email-review-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="token" value="<?php echo escape($token); ?>">

                <div class="form-group">
                    <label>How would you rate this product? *</label>
                    <div class="star-rating-input" id="starRatingInput">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-rating="<?php echo $i; ?>">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" required>
                </div>

                <div class="form-group">
                    <label for="reviewTitle">Review Title (optional)</label>
                    <input type="text" id="reviewTitle" name="title" maxlength="255"
                           placeholder="Summarize your experience in a few words">
                </div>

                <div class="form-group">
                    <label for="reviewText">Your Review (optional)</label>
                    <textarea id="reviewText" name="review_text" rows="5"
                              placeholder="Tell us what you loved about this product, how it fits, the quality, etc."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-large">Submit Review</button>
            </form>

            <p class="review-note">
                Thank you for taking the time to share your experience!
                Your review helps other customers make informed decisions.
            </p>
        </div>
    </div>
</div>

<style>
.email-review-page {
    padding: 60px 0;
    background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
    min-height: 80vh;
}

.review-card-wrapper {
    max-width: 600px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.review-card-wrapper h1 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
}

.product-to-review {
    text-align: center;
    padding: 20px;
    background: #fdf2f8;
    border-radius: 12px;
    margin-bottom: 30px;
}

.product-review-image {
    max-width: 200px;
    height: auto;
    border-radius: 8px;
    margin-bottom: 15px;
}

.product-to-review h2 {
    font-size: 1.2rem;
    color: #333;
    margin: 0;
}

.email-review-form .form-group {
    margin-bottom: 25px;
}

.email-review-form label {
    display: block;
    font-weight: 500;
    margin-bottom: 10px;
    color: #333;
}

.email-review-form input[type="text"],
.email-review-form textarea {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #eee;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.email-review-form input[type="text"]:focus,
.email-review-form textarea:focus {
    outline: none;
    border-color: #ec4899;
}

.star-rating-input {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.star-rating-input .star {
    font-size: 3rem;
    color: #d1d5db;
    cursor: pointer;
    transition: color 0.2s, transform 0.2s;
}

.star-rating-input .star:hover,
.star-rating-input .star.selected {
    color: #fbbf24;
    transform: scale(1.15);
}

.btn-large {
    width: 100%;
    padding: 16px 30px;
    font-size: 1.1rem;
}

.review-note {
    text-align: center;
    color: #999;
    font-size: 0.9rem;
    margin-top: 25px;
    margin-bottom: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('#starRatingInput .star');
    const ratingInput = document.getElementById('ratingInput');
    let selectedRating = 0;

    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            selectedRating = index + 1;
            ratingInput.value = selectedRating;

            stars.forEach((s, i) => {
                s.classList.toggle('selected', i < selectedRating);
            });
        });

        star.addEventListener('mouseenter', function() {
            stars.forEach((s, i) => {
                s.style.color = i <= index ? '#fbbf24' : '#d1d5db';
            });
        });
    });

    document.getElementById('starRatingInput').addEventListener('mouseleave', function() {
        stars.forEach((s, i) => {
            s.style.color = i < selectedRating ? '#fbbf24' : '#d1d5db';
        });
    });

    // Form validation
    document.querySelector('.email-review-form').addEventListener('submit', function(e) {
        if (!selectedRating) {
            e.preventDefault();
            alert('Please select a rating.');
        }
    });
});
</script>
