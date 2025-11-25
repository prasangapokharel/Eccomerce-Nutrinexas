;(() => {
  // Configuration
  const config = {
    couponCode: "TIHAR",
    discount: "200Rs OFF",
    title: "Special Tihar Offer!",
    description: "Use this code at checkout",
    autoShow: true,
    showDelay: 1000,
    cookieName: "tihar_coupon_claimed",
    cookieDays: 365,
  }

  function setCookie(name, value, days) {
    const date = new Date()
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000)
    const expires = "expires=" + date.toUTCString()
    document.cookie = name + "=" + value + ";" + expires + ";path=/"
  }

  function getCookie(name) {
    const nameEQ = name + "="
    const ca = document.cookie.split(";")
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i]
      while (c.charAt(0) === " ") c = c.substring(1, c.length)
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length)
    }
    return null
  }

  // Create coupon drawer
  function createCouponOverlay() {
    const overlay = document.createElement("div")
    overlay.id = "tihar-coupon-overlay"
    overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 10000;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
      backdrop-filter: blur(5px);
      overflow: hidden;
    `

    const card = document.createElement("div")
    card.style.cssText = `
      background: linear-gradient(135deg, #0A3167 0%, #C5A572 100%);
      border-radius: 25px 25px 0 0;
      padding: 30px 25px;
      width: 100%;
      max-width: 500px;
      height: auto;
      min-height: 400px;
      max-height: 80vh;
      box-shadow: 0 -8px 25px rgba(0, 0, 0, 0.3);
      transform: translateY(100%);
      transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      position: fixed;
      bottom: 0;
      left: 50%;
      margin-left: -250px;
      overflow-y: auto;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      box-sizing: border-box;
    `
    
    // Add responsive styles for mobile
    if (window.innerWidth <= 768) {
      card.style.width = "95vw"
      card.style.maxWidth = "none"
      card.style.left = "50%"
      card.style.marginLeft = "-47.5vw"
      card.style.borderRadius = "25px 25px 0 0"
      card.style.padding = "25px 20px"
      card.style.background = "linear-gradient(135deg, #0A3167 0%, #C5A572 100%)"
    }

    // Close button
    const closeBtn = document.createElement("button")
    closeBtn.innerHTML = "×"
    closeBtn.style.cssText = `
      position: absolute;
      top: 15px;
      right: 15px;
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      font-size: 20px;
      font-weight: bold;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1;
    `
    
    closeBtn.onmouseover = () => {
      closeBtn.style.background = "rgba(255, 255, 255, 0.3)"
      closeBtn.style.transform = "scale(1.1)"
    }
    closeBtn.onmouseout = () => {
      closeBtn.style.background = "rgba(255, 255, 255, 0.2)"
      closeBtn.style.transform = "scale(1)"
    }
    closeBtn.onclick = () => removeCoupon()

    // Decorative elements
    const deco1 = document.createElement("div")
    deco1.style.cssText = `
      position: absolute;
      top: -50px;
      right: -50px;
      width: 150px;
      height: 150px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    `

    const deco2 = document.createElement("div")
    deco2.style.cssText = `
      position: absolute;
      bottom: -30px;
      left: -30px;
      width: 100px;
      height: 100px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    `

    // Title
    const title = document.createElement("h2")
    title.textContent = config.title
    title.style.cssText = `
      color: white;
      font-size: 28px;
      font-weight: 800;
      margin: 0 0 8px 0;
      text-align: center;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
      line-height: 1.2;
    `

    // Discount badge
    const badge = document.createElement("div")
    badge.textContent = config.discount
    badge.style.cssText = `
      background: rgba(255, 255, 255, 0.15);
      color: white;
      font-size: 20px;
      font-weight: 800;
      padding: 10px 20px;
      border-radius: 15px;
      border: 2px solid rgba(255, 255, 255, 0.4);
      display: inline-block;
      margin: 12px 0;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      backdrop-filter: blur(10px);
    `

    const badgeContainer = document.createElement("div")
    badgeContainer.style.textAlign = "center"
    badgeContainer.appendChild(badge)

    // Description
    const description = document.createElement("p")
    description.textContent = config.description
    description.style.cssText = `
      color: rgba(255, 255, 255, 0.95);
      font-size: 14px;
      margin: 15px 0 20px 0;
      text-align: center;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    `

    // Coupon code display
    const codeContainer = document.createElement("div")
    codeContainer.style.cssText = `
      background: rgba(255, 255, 255, 0.1);
      border: 2px dashed rgba(255, 255, 255, 0.3);
      border-radius: 15px;
      padding: 15px;
      margin: 20px 0;
      text-align: center;
    `
    
    const codeLabel = document.createElement("p")
    codeLabel.textContent = "Use this code at checkout"
    codeLabel.style.cssText = `
      color: rgba(255, 255, 255, 0.8);
      font-size: 14px;
      margin: 0 0 8px 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    `
    
    const couponCode = document.createElement("div")
    couponCode.textContent = "TIHAR"
    couponCode.style.cssText = `
      color: white;
      font-size: 24px;
      font-weight: 800;
      font-family: 'Courier New', monospace;
      letter-spacing: 2px;
      margin: 0;
    `
    
    codeContainer.appendChild(codeLabel)
    codeContainer.appendChild(couponCode)

    const claimBtn = document.createElement("button")
    claimBtn.textContent = "Claim Offer"
    claimBtn.className = "clip"
    claimBtn.style.cssText = `
      background: #C5A572;
      color: white;
      border: 2px solid rgba(255, 255, 255, 0.3);
      padding: 12px 30px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      margin-top: 20px;
      width: 200px;
      min-width: 180px;
      max-width: 220px;
      align-self: center;
    `

    claimBtn.onmouseover = () => {
      claimBtn.style.background = "#0A3167"
      claimBtn.style.transform = "translateY(-2px)"
      claimBtn.style.boxShadow = "0 6px 16px rgba(0, 0, 0, 0.3)"
    }
    claimBtn.onmouseout = () => {
      claimBtn.style.background = "#C5A572"
      claimBtn.style.transform = "translateY(0)"
      claimBtn.style.boxShadow = "0 4px 12px rgba(0, 0, 0, 0.2)"
    }

    claimBtn.onclick = function(e) {
      e.preventDefault()
      
      // Copy coupon code to clipboard
      const couponCode = "TIHAR"
      
      // Try modern clipboard API first
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(couponCode).then(() => {
          showSuccess()
        }).catch(() => {
          fallbackCopy()
        })
      } else {
        fallbackCopy()
      }
      
      function fallbackCopy() {
        // Fallback method for older browsers
        const textArea = document.createElement("textarea")
        textArea.value = couponCode
        textArea.style.position = "fixed"
        textArea.style.left = "-999999px"
        textArea.style.top = "-999999px"
        document.body.appendChild(textArea)
        textArea.focus()
        textArea.select()
        
        try {
          document.execCommand('copy')
          showSuccess()
        } catch (err) {
          showSuccess() // Show success anyway
        }
        
        document.body.removeChild(textArea)
      }
      
      function showSuccess() {
        // Show success message
        claimBtn.textContent = "✓ Code Copied!"
        claimBtn.style.background = "#4CAF50"
        claimBtn.style.color = "white"
        
        // Set cookie and close after 1.5 seconds
        setCookie(config.cookieName, "true", config.cookieDays)
        setTimeout(() => {
          removeCoupon()
        }, 1500)
      }
    }

    // Assemble card
    card.appendChild(closeBtn)
    card.appendChild(deco1)
    card.appendChild(deco2)
    card.appendChild(title)
    card.appendChild(badgeContainer)
    card.appendChild(description)
    card.appendChild(codeContainer)
    card.appendChild(claimBtn)

    overlay.appendChild(card)
    document.body.appendChild(overlay)

    return { overlay, card }
  }

  // Show copy success animation
  function showCopySuccess(button) {
    button.textContent = "✓ Copied!"
    button.style.background = "#10b981"
    button.style.color = "white"
  }

  // Fallback copy method
  function fallbackCopy(text, button) {
    const textarea = document.createElement("textarea")
    textarea.value = text
    textarea.style.position = "fixed"
    textarea.style.opacity = "0"
    document.body.appendChild(textarea)
    textarea.select()

    try {
      document.execCommand("copy")
      showCopySuccess(button)
      // Set cookie and close after 1.5 seconds
      setCookie(config.cookieName, "true", config.cookieDays)
      setTimeout(() => {
        removeCoupon()
      }, 1500)
    } catch (err) {
      button.textContent = "Failed to copy"
      setTimeout(() => {
        button.textContent = "Claim"
      }, 2000)
    }

    document.body.removeChild(textarea)
  }

  // Show coupon drawer
  function showCoupon() {
    const existing = document.getElementById("tihar-coupon-overlay")
    if (existing) {
      existing.style.pointerEvents = "all"
      existing.style.opacity = "1"
      const card = existing.querySelector("div")
      if (card) {
        card.style.transform = "translateY(0)"
      }
      return
    }

    const { overlay, card } = createCouponOverlay()

    setTimeout(() => {
      overlay.style.pointerEvents = "all"
      overlay.style.opacity = "1"
      card.style.transform = "translateY(0)"
    }, 10)
  }

  function removeCoupon() {
    const overlay = document.getElementById("tihar-coupon-overlay")
    if (overlay) {
      overlay.style.opacity = "0"
      const card = overlay.querySelector("div")
      if (card) {
        card.style.transform = "translateY(100%)"
      }
      setTimeout(() => {
        overlay.remove()
      }, 400)
    }
  }

  // Initialize
  function init() {
    if (getCookie(config.cookieName) === "true") {
      return // Don't show if already claimed
    }

    if (config.autoShow) {
      setTimeout(() => {
        showCoupon()
      }, config.showDelay)
    }

    // Expose global functions
    window.showTiharCoupon = showCoupon
    window.removeTiharCoupon = removeCoupon
  }

  // Auto-initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init)
  } else {
    init()
  }
})()
