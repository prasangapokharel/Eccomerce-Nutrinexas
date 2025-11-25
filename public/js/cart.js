// assets/js/cart.js
function updateCartItem(productId, action) {
  fetch(ASSETS_URL + "/cart/update", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: `product_id=${productId}&action=${action}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const cartCountElements = document.querySelectorAll(".cart-count")
        cartCountElements.forEach((element) => {
          element.textContent = data.cart_count
        })
        document.getElementById("subtotal").textContent = Number.parseFloat(data.cart_total).toFixed(2)
        document.getElementById("tax").textContent = Number.parseFloat(data.tax).toFixed(2)
        document.getElementById("final-total").textContent = Number.parseFloat(data.final_total).toFixed(2)
        location.reload()
      }
    })
    .catch((error) => {
      console.error("Error:", error)
    })
}

function removeCartItem(cartItemIdOrProductId, productId) {
  // If productId provided, prefer AJAX POST using cart_item_id
  if (typeof productId !== "undefined") {
    fetch(ASSETS_URL + "/cart/remove", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: `cart_item_id=${cartItemIdOrProductId}&product_id=${productId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data && data.success) {
          const cartCountElements = document.querySelectorAll(".cart-count")
          cartCountElements.forEach((element) => {
            element.textContent = data.cart_count
          })
          location.reload()
        } else if (data && data.message) {
          alert(data.message)
        }
      })
      .catch((error) => console.error("Error:", error))
    return
  }

  // Fallback: original behavior using product id in URL
  if (confirm("Are you sure you want to remove this item from your cart?")) {
    window.location.href = ASSETS_URL + "/cart/remove/" + cartItemIdOrProductId
  }
}

function clearCart() {
  if (confirm("Are you sure you want to clear your cart?")) {
    window.location.href = ASSETS_URL + "/cart/clear"
  }
}
