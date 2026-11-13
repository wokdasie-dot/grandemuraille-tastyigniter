<?php
$lunchActive = $todayOverride && $todayOverride->lunch_closed;
$dinnerActive = $todayOverride && $todayOverride->dinner_closed;
$t = fn (string $key): string => lang('restoline.content::default.admin.'.$key);
?>

<div class="d-flex p-3">
    <h4 class="page-title mb-0 lh-base"><?= e($t('daily_capacity.list_title')); ?></h4>
</div>

<div class="mx-3 mb-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title"><?= e(sprintf($t('daily_capacity.today_title'), now()->isoFormat('dddd D MMMM YYYY'))); ?></h5>
            <p class="text-muted"><?= e($t('daily_capacity.today_help')); ?></p>

            <div class="d-flex gap-3 flex-wrap">
                <button
                    type="button"
                    class="btn <?= $lunchActive ? 'btn-danger' : 'btn-outline-primary'; ?> btn-lg"
                    data-request="onToggleLunchToday"
                    data-request-flash
                    data-request-success="window.location.reload()"
                >
                    <i class="fa <?= $lunchActive ? 'fa-lock' : 'fa-unlock'; ?> mr-2"></i>
                    <?= e($lunchActive ? $t('daily_capacity.open_lunch') : $t('daily_capacity.close_lunch')); ?>
                </button>

                <button
                    type="button"
                    class="btn <?= $dinnerActive ? 'btn-danger' : 'btn-outline-primary'; ?> btn-lg"
                    data-request="onToggleDinnerToday"
                    data-request-flash
                    data-request-success="window.location.reload()"
                >
                    <i class="fa <?= $dinnerActive ? 'fa-lock' : 'fa-unlock'; ?> mr-2"></i>
                    <?= e($dinnerActive ? $t('daily_capacity.open_dinner') : $t('daily_capacity.close_dinner')); ?>
                </button>
            </div>

            <?php if ($todayOverride): ?>
                <div class="mt-3 small">
                    <span class="text-muted"><?= e($t('daily_capacity.current_status')); ?></span>
                    <span class="badge <?= $todayOverride->lunch_closed ? 'badge-danger' : 'badge-success'; ?> ml-1"><?= e($t('daily_capacity.field_lunch')); ?> <?= e($todayOverride->lunch_closed ? $t('daily_capacity.status_full') : $t('daily_capacity.status_open')); ?></span>
                    <span class="badge <?= $todayOverride->dinner_closed ? 'badge-danger' : 'badge-success'; ?> ml-1"><?= e($t('daily_capacity.field_dinner')); ?> <?= e($todayOverride->dinner_closed ? $t('daily_capacity.status_full') : $t('daily_capacity.status_open')); ?></span>
                    <?php if ($todayOverride->reason): ?>
                        <span class="text-muted ml-2">(<?= e($todayOverride->reason); ?>)</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="mt-3 small text-muted"><?= e($t('daily_capacity.no_restriction')); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row-fluid">
    <div class="card shadow-sm mx-3">
        <div class="border-bottom">
            <div class="row">
                <div class="col-lg-9 pe-0">
                    <?= $this->renderListToolbar(); ?>
                </div>
                <div class="col-lg-3">
                    <?= $this->renderListFilter(); ?>
                </div>
            </div>
        </div>
        <?= $this->renderList(null, true); ?>
    </div>
</div>
