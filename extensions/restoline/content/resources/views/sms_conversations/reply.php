<?php
$conversationId = (int) $model->getKey();
$textareaId = 'sms-manual-reply-'.$conversationId;
?>
<div class="border rounded p-3">
    <label for="<?= e($textareaId) ?>" class="form-label">Votre réponse au client</label>
    <textarea id="<?= e($textareaId) ?>" class="form-control mb-2" rows="4" maxlength="1000"
        placeholder="Écrire la réponse SMS de l’équipe..."></textarea>
    <button type="button" class="btn btn-primary"
        data-request="onSendReply"
        data-request-data="conversation_id: <?= $conversationId ?>, message: ''"
        onclick="var f=document.getElementById('<?= e($textareaId) ?>'); var m=f ? f.value.trim() : ''; if (!m) return false; this.setAttribute('data-request-data', 'conversation_id: <?= $conversationId ?>, message: '+JSON.stringify(m));">
        Envoyer le SMS
    </button>
</div>
