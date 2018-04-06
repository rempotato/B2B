<?php
$fieldOptions = $field->options();
$checkedValues = (array)$field->value;
$isScrollable = count($fieldOptions) > 10;
?>
<?php if ($this->previewMode && $field->value) { ?>

    <div class="field-checkboxlist">
        <?php $index = 0;
        foreach ($fieldOptions as $value => $option) { ?>
            <?php
            $index++;
            $checkboxId = 'checkbox_'.$field->getId().'_'.$index;
            if (!in_array($value, $checkedValues)) continue;
            if (!is_array($option)) $option = [$option];
            ?>
            <div class="checkbox custom-checkbox">
                <input
                    type="checkbox"
                    id="<?= $checkboxId ?>"
                    name="<?= $field->getName() ?>[]"
                    value="<?= $value ?>"
                    disabled="disabled"
                    checked="checked">

                <label for="<?= $checkboxId ?>">
                    <?= e((sscanf($option[0], 'lang:%s', $line) === 1) ? lang($line) : $option[0]) ?>
                </label>
                <?php if (isset($option[1])) { ?>
                    <p class="help-block"><?= e((sscanf($option[1], 'lang:%s', $line) === 1) ? lang($line) : $option[1]) ?></p>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

<?php } elseif (!$this->previewMode AND count($fieldOptions)) { ?>

    <div class="field-checkboxlist <?= $isScrollable ? 'is-scrollable' : '' ?>">
        <?php if ($isScrollable) { ?>
        <small>
            <?= e(lang('admin::default.text_select')) ?>:
            <a href="javascript:;" data-field-checkboxlist-all><?= e(lang('admin::default.text_select_all')) ?></a>,
            <a href="javascript:;" data-field-checkboxlist-none><?= e(lang('admin::default.text_select_none')) ?></a>
        </small>

        <div class="field-checkboxlist-scrollable">
            <div class="scrollbar">
                <?php } ?>

                <input
                    type="hidden"
                    name="<?= $field->getName() ?>"
                    value="0"/>

                <?php $index = 0;
                foreach ($fieldOptions as $value => $option) { ?>
                    <?php
                    $index++;
                    $checkboxId = 'checkbox_'.$field->getId().'_'.$index;
                    if (is_string($option)) $option = [$option];
                    ?>
                    <div class="checkbox custom-checkbox">
                        <input
                            type="checkbox"
                            id="<?= $checkboxId ?>"
                            name="<?= $field->getName() ?>[]"
                            value="<?= $value ?>"
                            <?= in_array($value, $checkedValues) ? 'checked="checked"' : '' ?>>

                        <label for="<?= $checkboxId ?>">
                            <?= isset($option[0]) ? e(lang($option[0])) : '&nbsp;' ?>
                        </label>
                        <?php if (isset($option[1])) { ?>
                            <p class="help-block"><?= e(lang($option[1])) ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if ($isScrollable) { ?>
            </div>
        </div>
    <?php } ?>

    </div>

<?php } else { ?>

    <?php if ($field->placeholder) { ?>
        <p><?= e(lang($field->placeholder)) ?></p>
    <?php } ?>

<?php } ?>
