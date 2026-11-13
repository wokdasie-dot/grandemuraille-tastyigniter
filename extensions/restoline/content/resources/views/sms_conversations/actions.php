<?php
$conversationId = (int)$model->getKey();
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <button type="button" class="btn btn-warning" data-request="onTakeOver" data-request-data="conversation_id: <?= $conversationId ?>">Prendre la main</button>
    <button type="button" class="btn btn-light" data-request="onResumeAutomation" data-request-data="conversation_id: <?= $conversationId ?>">Reprendre l’automatisation</button>
</div>
<div class="border rounded p-3">
    <label for="sms-manual-reply-<?= $conversationId ?>" class="form-label">Répondre au client par SMS</label>
    <textarea id="sms-manual-reply-<?= $conversationId ?>" class="form-control mb-2" rows="3" maxlength="1000" placeholder="Écrivez la réponse de l’équipe..."></textarea>
    <button type="button" class="btn btn-primary" data-request="onSendReply" data-request-data="conversation_id: <?= $conversationId ?>, message: ''" onclick="this.setAttribute('data-request-data', 'conversation_id: <?= $conversationId ?>, message: '+JSON.stringify(document.getElementById('sms-manual-reply-<?= $conversationId ?>').value))">Envoyer le SMS</button>
</div>
