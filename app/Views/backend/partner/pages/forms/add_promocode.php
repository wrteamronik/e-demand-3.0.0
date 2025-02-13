<div class="main-content">
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('add_promocodes', 'Add Promocode') ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/partner/dashboard') ?>"> <i class="fas fa-home-alt text-primary"></i><?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item"><a href="<?= base_url('partner/promo_codes') ?>"> <?= labels('promocode', 'Promocodes') ?></a></div>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <form method="post" action="<?= base_url('partner/promo_codes/save') ?>" id="promo_code_form" class="form-submit-event">
                            <div class="row pl-3">
                                <div class="col border_bottom_for_cards">
                                    <div class="toggleButttonPostition"><?= labels('add_promocodes', 'Add Promocode') ?></div>
                                </div>
                                <div class="col d-flex justify-content-end mr-3 mt-4 border_bottom_for_cards">
                                    <input type="checkbox" id="promocode_status" name="status" class="status-switch">
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="promo_code" class="required"><?= labels('promocode', 'Promocode') ?></label>
                                            <input type="text" class="form-control" id="promo_code" name="promo_code">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="start_date" class="required"><?= labels('start_date', 'Start Date') ?></label>
                                            <input type="text" class="form-control datepicker" id="start_date" name="start_date">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="end_date" class="required"><?= labels('end_date', 'End Date') ?></label>
                                            <input type="text" class="form-control datepicker" id="end_date" name="end_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="no_of_users" class="required"><?= labels('no_of_users', 'No. of users') ?></label>
                                            <i data-content=" <?= labels('data_content_for_no_of_user', "Only the first X number of users can apply it. For example, if you have allowed 10, then the first 10 users can use this promo code.") ?>" class="fa fa-question-circle" data-original-title="" title=""></i>
                                            <input type="number" class="form-control" id="no_of_users" name="no_of_users" min="0" oninput="this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="minimum_order_amount" class="required"><?= labels('minimum_order_amount', 'Minimum order amount') ?></label>
                                            <i data-content=" <?= labels('data_content_for_minimum_booking_amount', "Customers can apply a promo code if the subtotal of their service is higher than the Minimum Booking amount.") ?>" class="fa fa-question-circle" data-original-title="" title=""></i>
                                            <input type="number" class="form-control" id="minimum_order_amount" name="minimum_order_amount" min="0" oninput="this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="form-group">
                                                <label for="message" class=""> <?= labels('message', 'Message') ?> </label>
                                                <textarea style="min-height:60px" id="message" class="form-control h-25 border" name="message"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="discount" class="required"><?= labels('discount', 'Discount') ?></label>
                                            <input type="number" class="form-control" id="discount" name="discount" min="0" oninput="this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="discount_type" class="required"><?= labels('discount_type', 'Discount Type') ?></label>
                                            <i data-content=" <?= labels('data_content_for_max_discount_amount', "You want to offer a discount based on a percentage or a fixed amount of the total cost of the services.") ?>" class="fa fa-question-circle" data-original-title="" title=""></i>
                                            <select name="discount_type" id="discount_type" class="form-control">
                                                <option value="amount"><?= labels('amount', 'Amount') ?></option>
                                                <option value="percentage"><?= labels('percentage', 'Percentage') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="max_discount_amount"><?= labels('max_discount_amount', 'Max Discount Amount') ?></label>
                                            <i data-content=" <?= labels('data_content_for_discount_type', "This promo code gives customers a maximum discount of X amount.") ?>" class="fa fa-question-circle" data-original-title="" title=""></i>
                                            <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="image" class="required"><?= labels('image', 'Image') ?></label>
                                            <input type="file" class="filepond" id="image" name="image" accept="image/*">
                                            <input type="hidden" name="old_image" id="old_image" value="">
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="custom-switch mt-2">
                                            <span class="custom-switch-description"><?= labels('repeat_usage', 'Repeat Usage ?') ?></span>
                                            <i data-content=" <?= labels('data_content_for_repeat_usage', "If it's allowed, customers can use this promo code many times.") ?>" class="fa fa-question-circle" data-original-title="" title=""></i>
                                            <input type="checkbox" id="repeat_usage" name="repeat_usage" class="status-switch editRepeatUsageInModel">
                                        </label>
                                    </div>
                                    <div class="col-md-4 repeat_usage">
                                        <div class="form-group" class="required">
                                            <label for="no_of_repeat_usage"><?= labels('no_of_repeat_usage', 'No. of repeat usage') ?></label>
                                            <i data-content=" <?= labels('data_content_for_no_of_repeat_usage', "customers can use the promo code a certain number of times. For example, if you set it to 10, customers can use the promo code up to 10 times when booking the services, as long as the conditions are met.") ?>" class="fa fa-question-circle" data-original-title="" title=""></i>
                                            <input type="number" class="form-control" id="no_of_repeat_usage" name="no_of_repeat_usage" min="0" oninput="this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md d-flex justify-content-end">
                                        <button class="btn btn-primary" type="submit"><?= labels('add_promocodes', 'Add Promo Code') ?></button>
                                    </div>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</div>
</section>
</div>
<script>
    $('#start_date').change(function() {
        var doc = $('#start_date').val();
        $("#end_date").daterangepicker({
            locale: {
                format: "YYYY-MM-DD",
            },
            minDate: new Date(doc),
            singleDatePicker: true,
        });
    });
    $(document).ready(function() {
        $('#repeat_usage').siblings('.switchery').addClass('not_allowed-content').removeClass('allowed-content');
        $('#promocode_status').siblings('.switchery').addClass('deactive-content').removeClass('active-content');

        function handleSwitchChange(checkbox) {
            var switchery = checkbox.nextElementSibling;
            if (checkbox.checked) {
                switchery.classList.add('active-content');
                switchery.classList.remove('deactive-content');
            } else {
                switchery.classList.add('deactive-content');
                switchery.classList.remove('active-content');
            }
        }

        function handleRepeatSwitchChange(checkbox) {
            var switchery1 = checkbox.nextElementSibling;
            if (checkbox.checked) {
                switchery1.classList.add('allowed-content');
                switchery1.classList.remove('not_allowed-content');
            } else {
                switchery1.classList.add('not_allowed-content');
                switchery1.classList.remove('allowed-content');
            }
        }
        var repeat_usage = document.querySelector('#repeat_usage');
        repeat_usage.addEventListener('change', function() {
            handleRepeatSwitchChange(repeat_usage);
        });
        var promocode_status = document.querySelector('#promocode_status');
        promocode_status.addEventListener('change', function() {
            handleSwitchChange(promocode_status);
        });
    });
</script>
<script>
    $(function() {
        $('.fa').popover({
            trigger: "hover"
        });
    })
</script>