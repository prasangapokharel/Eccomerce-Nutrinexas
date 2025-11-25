/**
 * Tihar.js - Attractive Tihar/Diwali Themed Animation
 * Pure vanilla JavaScript - No frameworks required
 * Features: Rocket blasts, festive particles
 */

;(() => {
  // Configuration
  const CONFIG = {
    colors: {
      fireworks: ["#FF6B35", "#F7931E", "#FDC830", "#FF0080", "#7928CA", "#FF4D4D", "#FFD700"],
      sparkle: ["#FFD700", "#FFF8DC", "#FFFFE0", "#FFFACD"],
    },
    fireworksInterval: 800,
    particleCount: 70,
  }

  // Create and setup canvas
  const canvas = document.createElement("canvas")
  const ctx = canvas.getContext("2d")
  canvas.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;"
  document.body.appendChild(canvas)

  function resizeCanvas() {
    canvas.width = window.innerWidth
    canvas.height = window.innerHeight
  }
  resizeCanvas()
  window.addEventListener("resize", resizeCanvas)

  // Particle class for fireworks
  class Particle {
    constructor(x, y, color, velocity) {
      this.x = x
      this.y = y
      this.color = color
      this.velocity = velocity
      this.alpha = 1
      this.decay = Math.random() * 0.015 + 0.015
      this.gravity = 0.05
      this.friction = 0.98
      this.size = Math.random() * 3 + 2
    }

    update() {
      this.velocity.x *= this.friction
      this.velocity.y *= this.friction
      this.velocity.y += this.gravity
      this.x += this.velocity.x
      this.y += this.velocity.y
      this.alpha -= this.decay
    }

    draw() {
      ctx.save()
      ctx.globalAlpha = this.alpha
      ctx.fillStyle = this.color
      ctx.beginPath()
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2)
      ctx.fill()

      // Glow effect
      ctx.globalAlpha = this.alpha * 0.5
      ctx.beginPath()
      ctx.arc(this.x, this.y, this.size * 2, 0, Math.PI * 2)
      ctx.fill()
      ctx.restore()
    }
  }

  // Rocket class
  class Rocket {
    constructor(x, targetY, size = 1) {
      this.x = x
      this.y = canvas.height
      this.targetY = targetY
      this.velocity = -8 - Math.random() * 4
      this.color = CONFIG.colors.fireworks[Math.floor(Math.random() * CONFIG.colors.fireworks.length)]
      this.exploded = false
      this.trail = []
      this.size = size
    }

    update() {
      if (!this.exploded) {
        this.y += this.velocity
        this.trail.push({ x: this.x, y: this.y, alpha: 1 })
        if (this.trail.length > 10) this.trail.shift()

        if (this.y <= this.targetY) {
          this.explode()
        }
      }
    }

    draw() {
      if (!this.exploded) {
        // Draw trail
        this.trail.forEach((point, index) => {
          ctx.save()
          ctx.globalAlpha = (index / this.trail.length) * 0.5
          ctx.fillStyle = this.color
          ctx.beginPath()
          ctx.arc(point.x, point.y, 2 * this.size, 0, Math.PI * 2)
          ctx.fill()
          ctx.restore()
        })

        // Draw rocket
        ctx.fillStyle = this.color
        ctx.beginPath()
        ctx.arc(this.x, this.y, 3 * this.size, 0, Math.PI * 2)
        ctx.fill()
      }
    }

    explode() {
      this.exploded = true
      const particleCount = Math.floor(CONFIG.particleCount * this.size)

      for (let i = 0; i < particleCount; i++) {
        const angle = (Math.PI * 2 * i) / particleCount
        const velocity = {
          x: Math.cos(angle) * (Math.random() * 6 + 2) * this.size,
          y: Math.sin(angle) * (Math.random() * 6 + 2) * this.size,
        }
        particles.push(new Particle(this.x, this.y, this.color, velocity))
      }

      // Add extra sparkles
      for (let i = 0; i < Math.floor(20 * this.size); i++) {
        const angle = Math.random() * Math.PI * 2
        const velocity = {
          x: Math.cos(angle) * (Math.random() * 3 + 1) * this.size,
          y: -Math.random() * 2 - 1,
        }
        const sparkleColor = CONFIG.colors.sparkle[Math.floor(Math.random() * CONFIG.colors.sparkle.length)]
        particles.push(new Particle(this.x, this.y, sparkleColor, velocity))
      }
    }
  }

  // Arrays to hold objects
  const particles = []
  const rockets = []

  // Auto-launch rockets
  setInterval(launchRocket, CONFIG.fireworksInterval)

  setTimeout(() => {
    canvas.style.transition = "opacity 0.5s ease-out"
    canvas.style.opacity = "0"
    setTimeout(() => {
      canvas.remove()
    }, 500)
  }, 12000)

  // Animation loop
  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height)

    // Update and draw particles
    for (let i = particles.length - 1; i >= 0; i--) {
      particles[i].update()
      particles[i].draw()
      if (particles[i].alpha <= 0) {
        particles.splice(i, 1)
      }
    }

    // Update and draw rockets
    for (let i = rockets.length - 1; i >= 0; i--) {
      rockets[i].update()
      rockets[i].draw()
      if (rockets[i].exploded) {
        rockets.splice(i, 1)
      }
    }

    requestAnimationFrame(animate)
  }

  // Launch rocket
  function launchRocket() {
    const x = Math.random() * canvas.width
    const targetY = Math.random() * (canvas.height * 0.3) + canvas.height * 0.1
    const rand = Math.random()
    const size = rand < 0.7 ? 1 : rand < 0.9 ? 1.5 : 2
    rockets.push(new Rocket(x, targetY, size))
  }

  // Start animation
  animate()

  setTimeout(() => launchRocket(), 300)
  setTimeout(() => launchRocket(), 600)
  setTimeout(() => launchRocket(), 900)
  setTimeout(() => launchRocket(), 1200)
  setTimeout(() => launchRocket(), 1500)
})()
