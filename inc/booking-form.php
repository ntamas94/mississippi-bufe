<?php
/** Foglalási űrlap. A feldolgozás a foglalas.php-ben történik; a session az i18n.php-ben indul. */
$tomorrow = (new DateTime('tomorrow', new DateTimeZone('Europe/Budapest')))->format('Y-m-d');
?>
<div class="form-card" id="foglalas" data-reveal>
  <h2><?= e(t('form.title')) ?></h2>
  <p class="lead"><?= e(t('form.lead')) ?></p>

  <form id="booking-form" action="foglalas.php" method="post" novalidate
        data-prices='<?= json_encode($CFG['room_prices']) ?>'
        data-texts='<?= json_encode([
            'required' => t('form.required'),
            'badEmail' => t('form.bad_email'),
            'badDate'  => t('form.bad_date'),
            'sending'  => t('form.sending'),
            'success'  => t('form.success'),
            'error'    => t('form.error'),
        ], JSON_UNESCAPED_UNICODE) ?>'>

    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
    <input type="hidden" name="lang" value="<?= e($LANG) ?>">
    <input type="hidden" name="ts" value="<?= time() ?>">
    <div class="hp" aria-hidden="true">
      <label for="website">Website</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="form-grid">
      <div class="field">
        <label for="f-name"><?= e(t('form.name')) ?> *</label>
        <input type="text" id="f-name" name="name" autocomplete="name" required>
        <span class="error"></span>
      </div>
      <div class="field">
        <label for="f-email"><?= e(t('form.email')) ?> *</label>
        <input type="email" id="f-email" name="email" autocomplete="email" required>
        <span class="error"></span>
      </div>
      <div class="field">
        <label for="f-phone"><?= e(t('form.phone')) ?></label>
        <input type="tel" id="f-phone" name="phone" autocomplete="tel">
        <span class="error"></span>
      </div>
      <div class="field">
        <label for="f-arrival"><?= e(t('form.arrival')) ?> *</label>
        <input type="date" id="f-arrival" name="arrival" min="<?= e($tomorrow) ?>" required>
        <span class="error"></span>
      </div>
      <div class="field">
        <label for="f-nights"><?= e(t('form.nights')) ?></label>
        <select id="f-nights" name="nights">
          <?php for ($i = 1; $i <= 14; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-guests"><?= e(t('form.guests')) ?></label>
        <select id="f-guests" name="guests">
          <?php foreach (array_keys($CFG['room_prices']) as $n): ?>
          <option value="<?= $n ?>"<?= $n === 2 ? ' selected' : '' ?>><?= $n ?> <?= e(t('motel.guests_unit')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field field--wide check">
        <input type="checkbox" id="f-breakfast" name="breakfast" value="1">
        <label for="f-breakfast"><?= e(t('form.breakfast')) ?></label>
      </div>
      <div class="field field--wide">
        <label for="f-message"><?= e(t('form.message')) ?></label>
        <textarea id="f-message" name="message" placeholder="<?= e(t('form.message_ph')) ?>"></textarea>
      </div>
    </div>

    <div class="estimate">
      <span><?= e(t('form.estimate')) ?></span>
      <span class="amount">—</span>
    </div>

    <p style="margin-top:22px;margin-bottom:0">
      <button class="btn btn--primary" type="submit"><?= e(t('form.submit')) ?></button>
    </p>

    <p class="form-note"><?= e(t('form.privacy')) ?></p>
    <div class="form-msg" hidden role="status"></div>
  </form>
</div>
