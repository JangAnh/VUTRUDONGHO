// ======= Lấy giá trị ban đầu =======
const deliveryCards = document.querySelectorAll(".delivery_card");
const deliveryFeeDisplay = document.getElementById("deliveryfee");
const totalDisplay = document.getElementById("totalPrice");
const hiddenShippingFee = document.getElementById("ShippingFee");
const hiddenTotal = document.getElementById("Total");
const discountDisplay = document.getElementById("discount");

const sumElement = document.getElementById("sum");
let sum = parseFloat(sumElement.dataset.sum.replace(/,/g, ''));  // xử lý dấu phẩy

let currentFee = 0;
let discountValue = 0;

// ======= Cập nhật tổng =======
function updateTotal() {
    const total = sum + currentFee - discountValue;

    totalDisplay.textContent = total.toLocaleString("vi-VN") + " $";
    hiddenTotal.value = total;
}

// ======= Chọn phương thức giao hàng =======
deliveryCards.forEach(card => {
    card.addEventListener("click", () => {

        deliveryCards.forEach(c => c.classList.remove("card_active"));
        card.classList.add("card_active");

        const fee = parseFloat(card.dataset.deliveryfee);  // ✔ Sửa parseInt → parseFloat
        currentFee = fee;

        deliveryFeeDisplay.textContent = fee.toLocaleString("vi-VN") + " $";
        hiddenShippingFee.value = fee;

        updateTotal();
    });
});

// ======= Khởi động lần đầu =======
window.addEventListener("DOMContentLoaded", () => {
    const activeCard = document.querySelector(".delivery_card.card_active");
    if (activeCard) {
        currentFee = parseFloat(activeCard.dataset.deliveryfee);
        deliveryFeeDisplay.textContent = currentFee.toLocaleString("vi-VN") + " $";
        hiddenShippingFee.value = currentFee;
        updateTotal();
    }
});

// ======= Voucher (chỉ giữ phần này nếu bạn có voucher) =======
function load_total() {
    const discountText = document.querySelector(".payment_detail_pricetotal[data-total]");
    discountValue = parseFloat(discountText.dataset.total) || 0;
    updateTotal();
}

// ======= Voucher apply handler (search by VoucherName) =======
document.addEventListener("DOMContentLoaded", () => {
    const voucherInput = document.getElementById("voucher_input");
    const applyBtn = document.querySelector(".submit_button");
    const discountTextEl = document.querySelector('.payment_detail_pricetotal[data-total]');
    const voucherNameContainer = document.getElementById("voucher_name_container");
    const voucherDiscountContainer = document.getElementById("voucher_discount");
    const hiddenVoucherId = document.getElementById("VoucherID");
    const hiddenOrderDiscount = document.getElementById("OrderDiscount");
    const voucherClearBtn = document.getElementById("voucher_clear_btn");

    if (!applyBtn || !voucherInput) return;

    applyBtn.addEventListener("click", (e) => {
        e.preventDefault();
        const code = voucherInput.value.trim();
        if (!code) {
            alert("Vui lòng nhập mã giảm giá.");
            return;
        }

        fetch(`modules/checkVoucher.php?VoucherName=${encodeURIComponent(code)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || "Mã giảm giá không hợp lệ.");
                    // reset UI
                    discountTextEl.dataset.total = 0;
                    discountDisplay.textContent = " $- 0";
                    voucherNameContainer.textContent = "";
                    voucherDiscountContainer.textContent = "";
                    hiddenVoucherId.value = "NULL";
                    hiddenOrderDiscount.value = 0;
                    discountValue = 0;
                    if (voucherClearBtn) voucherClearBtn.style.display = 'none';
                    updateTotal();
                    return;
                }

                const discount = parseFloat(data.Discount) || 0;
                const unit = (data.Unit || "").toString();
                let computedDiscount = 0;

                if (unit === "%") {
                    computedDiscount = (sum * discount) / 100;
                    voucherDiscountContainer.textContent = `${discount}%`;
                } else {
                    computedDiscount = discount;
                    voucherDiscountContainer.textContent = `${Number(discount).toLocaleString("vi-VN")} $`;
                }

                // update UI + hidden fields
                discountTextEl.dataset.total = computedDiscount;
                discountDisplay.textContent = " $- " + Number(computedDiscount).toLocaleString("vi-VN");
                voucherNameContainer.textContent = data.VoucherName || code;
                hiddenVoucherId.value = data.VoucherID || 'NULL';
                hiddenOrderDiscount.value = Math.round(computedDiscount);
                // show clear button
                if (voucherClearBtn) voucherClearBtn.style.display = 'inline-block';

                discountValue = computedDiscount;
                updateTotal();
            })
            .catch(err => {
                console.error(err);
                alert("Lỗi khi kiểm tra mã giảm giá.");
            });
    });

    // Clear voucher when user clicks the ✕ button
    if (voucherClearBtn) {
        voucherClearBtn.addEventListener('click', () => {
            discountTextEl.dataset.total = 0;
            discountDisplay.textContent = " $- 0";
            voucherNameContainer.textContent = "";
            voucherDiscountContainer.textContent = "";
            hiddenVoucherId.value = "NULL";
            hiddenOrderDiscount.value = 0;
            discountValue = 0;
            // hide button and clear input
            voucherClearBtn.style.display = 'none';
            if (voucherInput) voucherInput.value = '';
            updateTotal();
        });
    }

    // If already a discount value present on load, show clear button
    const initialDiscount = parseFloat(discountTextEl.dataset.total) || 0;
    if (initialDiscount > 0 && voucherClearBtn) voucherClearBtn.style.display = 'inline-block';
});