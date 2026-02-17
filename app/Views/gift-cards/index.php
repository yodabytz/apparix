<div class="container" style="padding: 3rem 1rem; max-width: 900px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 1rem;">Gift Cards</h1>
        <p style="color: #6b7280; font-size: 1.125rem; max-width: 600px; margin: 0 auto;">
            Give the gift of handmade! Our gift cards never expire and can be used on any product.
        </p>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 2rem;">
        Gift card purchased successfully! The recipient will receive an email with their gift card code.
    </div>
    <?php endif; ?>

    <div class="card" style="background: linear-gradient(135deg, #fff5f9 0%, #ffffff 100%); border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); margin-bottom: 2rem;">
        <!-- Gift Card Preview -->
        <div style="background: linear-gradient(135deg, #FF68C5 0%, #ff8fd4 100%); border-radius: 16px; padding: 2rem; color: white; margin-bottom: 2rem; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: relative; z-index: 1;">
                <img src="/assets/images/placeholder.png" alt="Apparix" style="height: 30px; filter: brightness(0) invert(1); margin-bottom: 1rem;">
                <p style="font-size: 0.875rem; opacity: 0.9; margin: 0 0 0.5rem 0;">Gift Card</p>
                <p id="previewAmount" style="font-size: 2.5rem; font-weight: 700; margin: 0;">$50</p>
            </div>
        </div>

        <form action="/gift-cards/purchase" method="POST">
            <input type="hidden" name="_csrf_token" value="<?php echo csrfToken(); ?>">

            <!-- Amount Selection -->
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.75rem;">Select Amount</label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                    <?php foreach ($denominations as $amount): ?>
                    <label class="amount-option" style="cursor: pointer;">
                        <input type="radio" name="amount" value="<?php echo $amount; ?>" <?php echo $amount === 50 ? 'checked' : ''; ?> style="display: none;">
                        <span style="display: block; padding: 1rem; text-align: center; border: 2px solid #e5e7eb; border-radius: 12px; font-weight: 600; transition: all 0.2s;">
                            $<?php echo $amount; ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 1rem;">
                    <label style="font-size: 0.875rem; color: #6b7280;">Or enter custom amount ($10-$500):</label>
                    <input type="number" id="customAmount" min="10" max="500" step="1" placeholder="Custom amount"
                           style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; margin-top: 0.5rem;">
                </div>
            </div>

            <!-- Recipient Info -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Recipient's Name *</label>
                    <input type="text" name="recipient_name" required placeholder="Their name"
                           style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Recipient's Email *</label>
                    <input type="email" name="recipient_email" required placeholder="their@email.com"
                           style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px;">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Your Name</label>
                <input type="text" name="sender_name" placeholder="Your name (optional)"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Personal Message</label>
                <textarea name="message" rows="3" placeholder="Add a personal message (optional)"
                          style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px; resize: vertical;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.125rem;">
                Purchase Gift Card
            </button>
        </form>
    </div>

    <!-- Check Balance Section -->
    <div class="card" style="background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem;">Check Gift Card Balance</h3>
        <div style="display: flex; gap: 0.75rem;">
            <input type="text" id="checkBalanceCode" placeholder="Enter gift card code"
                   style="flex: 1; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px;">
            <button type="button" onclick="checkBalance()" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                Check Balance
            </button>
        </div>
        <div id="balanceResult" style="margin-top: 1rem; display: none;"></div>
    </div>
</div>

<style>
.amount-option input:checked + span {
    border-color: #FF68C5;
    background: #fff5f9;
    color: #FF68C5;
}
.amount-option:hover span {
    border-color: #FF68C5;
}
</style>

<script>
// Update preview when amount changes
document.querySelectorAll('input[name="amount"]').forEach(input => {
    input.addEventListener('change', function() {
        document.getElementById('previewAmount').textContent = '$' + this.value;
        document.getElementById('customAmount').value = '';
    });
});

document.getElementById('customAmount').addEventListener('input', function() {
    if (this.value) {
        document.querySelectorAll('input[name="amount"]').forEach(i => i.checked = false);
        document.getElementById('previewAmount').textContent = '$' + this.value;
    }
});

document.getElementById('customAmount').addEventListener('change', function() {
    if (this.value) {
        // Create hidden input with custom amount
        const existingCustom = document.querySelector('input[name="amount"][type="hidden"]');
        if (existingCustom) existingCustom.remove();

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'amount';
        hidden.value = this.value;
        this.form.appendChild(hidden);
    }
});

async function checkBalance() {
    const code = document.getElementById('checkBalanceCode').value.trim();
    const resultDiv = document.getElementById('balanceResult');

    if (!code) {
        resultDiv.style.display = 'block';
        resultDiv.textContent = '';
        const errorP = document.createElement('p');
        errorP.style.color = '#ef4444';
        errorP.textContent = 'Please enter a gift card code.';
        resultDiv.appendChild(errorP);
        return;
    }

    try {
        const response = await fetch('/gift-cards/check-balance?code=' + encodeURIComponent(code));
        const data = await response.json();

        resultDiv.style.display = 'block';
        resultDiv.textContent = '';

        if (data.success) {
            const status = data.is_active ? 'Active' : 'Used';

            const container = document.createElement('div');
            container.style.cssText = 'background: #f0fdf4; padding: 1rem; border-radius: 10px;';

            const balanceP = document.createElement('p');
            balanceP.style.cssText = 'margin: 0 0 0.5rem 0; font-weight: 600; color: #166534;';
            balanceP.textContent = 'Balance: $' + parseFloat(data.balance).toFixed(2);

            const detailsP = document.createElement('p');
            detailsP.style.cssText = 'margin: 0; font-size: 0.875rem; color: #166534;';
            detailsP.textContent = 'Original Value: $' + parseFloat(data.original_amount).toFixed(2) + ' | Status: ' + status;

            container.appendChild(balanceP);
            container.appendChild(detailsP);
            resultDiv.appendChild(container);
        } else {
            const errorP = document.createElement('p');
            errorP.style.color = '#ef4444';
            errorP.textContent = data.error;
            resultDiv.appendChild(errorP);
        }
    } catch (e) {
        resultDiv.style.display = 'block';
        resultDiv.textContent = '';
        const errorP = document.createElement('p');
        errorP.style.color = '#ef4444';
        errorP.textContent = 'An error occurred. Please try again.';
        resultDiv.appendChild(errorP);
    }
}
</script>
