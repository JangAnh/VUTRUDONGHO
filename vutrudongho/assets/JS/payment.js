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

