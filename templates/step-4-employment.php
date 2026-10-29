<section class="repaf-step" data-step="6">
    <h3>Your Work and Occupation</h3>

    <?php
    $text_attrs = [
        'pattern' => "^[A-Za-zÀ-ÿ0-9 .,#/'&\\-]+$",
        'title'   => 'Letters, digits, spaces, and common punctuation only',
    ];
    $addr_attrs = $text_attrs;
    $zip_attrs  = [
        'pattern'     => '^[0-9]+$',
        'inputmode'   => 'numeric',
        'placeholder' => '0000',
        'title'       => 'Digits only',
    ];

    $work_people = [
        ['prefix' => 'buyer',  'title' => 'Your Information'],
        ['prefix' => 'spouse', 'title' => "Your Spouse's Info"],
        ['prefix' => 'co',     'title' => "Your Co-Borrower's Info"],
    ];

    foreach ($work_people as $work_person) {
        $work_prefix = $work_person['prefix'];
        $work_title  = $work_person['title'];
        include REPAF_PLUGIN_PATH . 'templates/partial-work-person.php';
    }
    ?>

    <div class="repaf-actions">
        <button type="button" class="repaf-back">Back</button>
        <button type="button" class="repaf-next">Next</button>
    </div>
</section>
