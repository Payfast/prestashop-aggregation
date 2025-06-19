$(document).ready(function () {
  let $payfastForm = $("input[name^='payfast_']");

  let isLive = $payfastForm.filter("[name='payfast_mode']:checked").val();
  let merchantID = $payfastForm.filter("[name='payfast_merchant_id']").val();
  let merchantKey = $payfastForm.filter("[name='payfast_merchant_key']").val();
  let isSplitPayment = $payfastForm.filter("[name='payfast_split_payments_enabled']:checked").val();
  let splitMerchantID = $payfastForm.filter("[name='payfast_split_payment_merchant_id']").val();
  let splitAmount = $payfastForm.filter("[name='payfast_split_payment_amount']").val();
  let splitPercentage = $payfastForm.filter("[name='payfast_split_payment_percentage']").val();
    let splitPaymentFields = ['payfast_split_payment_merchant_id', 'payfast_split_payment_amount', 'payfast_split_payment_percentage',
      'payfast_split_payment_min', 'payfast_split_payment_max'];
    let errorFields = [];

    if (isSplitPayment == 0) {
      splitPaymentFields.forEach(function (value) {
        $('[name="' + value + '"]').prop("disabled", true);
      });
    }

    $("input[name='payfast_mode']").change(function () {
      isLive = $("input[name='payfast_mode']:checked").val();
      if ($('#payfastDetailsError').html().length > 0) {
        validatePayfastForm();
      }
    });

  $(document).on("change", "input[name^='payfast_']", function () {
    let inputName = $(this).attr("name");

    switch (inputName) {
      case "payfast_merchant_id":
        merchantID = $(this).val();
        break;
      case "payfast_merchant_key":
        merchantKey = $(this).val();
        break;
      case "payfast_split_payment_amount":
        splitAmount = $(this).val();
        break;
      case "payfast_split_payment_percentage":
        splitPercentage = $(this).val();
        break;
      case "payfast_split_payment_merchant_id":
        splitMerchantID = $(this).val();
        break;
    }
    });

    $("input[name='payfast_split_payments_enabled']").change(function () {
      isSplitPayment = $("input[name='payfast_split_payments_enabled']:checked").val();
      if (isSplitPayment == 0) {
        splitPaymentFields.forEach(function (value) {
          $('[name="' + value + '"]').prop("disabled", true);
        });
      } else {
        splitPaymentFields.forEach(function (value) {
          $('[name="' + value + '"]').prop("disabled", false);
        });
      }
      if ($('#payfastDetailsError').html().length > 0) {
        validatePayfastForm();
      }
    });


    $('#pf__button').on("click", function (event) {
      if (!validatePayfastForm(true)) {
        event.preventDefault();
      }
    });

    function validatePayfastForm(saveChanges = false) {

      if ($('#payfastDetailsError').html().includes("Required: Merchant ID") || $('#payfastDetailsError').html().includes("Key") || saveChanges) {
        isLive = $("input[name='payfast_mode']:checked").val();
        if (isLive == 'live') {
          if (!merchantID) {
            errorFields.push("Merchant ID");
          }
          if (!merchantKey) {
            errorFields.push("Merchant Key");
          }
        }
        $("input[name='payfast_merchant_id']").css('border-color', (!merchantID && (isLive == 'live')) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
        $("input[name='payfast_merchant_key']").css('border-color', (!merchantKey && (isLive == 'live')) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
      }
      if ($('#payfastDetailsError').html().includes("Split") || saveChanges) {
        isSplitPayment = $("input[name='payfast_split_payments_enabled']:checked").val();
        if (isSplitPayment == 1) {
          if (!splitMerchantID) {
            errorFields.push("Split Payment Merchant ID");
          }
          if (!splitAmount && !splitPercentage) {
            errorFields.push("Split Payment Amount or Percentage");
          }
        }
        $("input[name='payfast_split_payment_merchant_id']").css('border-color', (!splitMerchantID && (isSplitPayment == 1)) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
        $("input[name='payfast_split_payment_amount']").css('border-color', (!splitAmount && !splitPercentage && (isSplitPayment == 1)) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
        $("input[name='payfast_split_payment_percentage']").css('border-color', (!splitAmount && !splitPercentage && (isSplitPayment == 1)) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
      }
      let total = errorFields.length;
      let message = "";
      $.each(errorFields, function (index, value) {
        if (index == total - 1) {
          message += value;
        } else {
          message += value + ", ";
        }
      });
      if (errorFields.length !== 0) {
        $('#payfastDetailsError').html("Required: " + message);
        $('#payfastDetailsError').css('display', 'block');
        errorFields = [];
        return false;
      } else {
        $('#payfastDetailsError').html("");
        return true;
      }
    }
  });
