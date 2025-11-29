/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./App/**/*.{php,js}",
    "./Components/**/*.{php,js}",
    "./resources/**/*.{php,html,txt}",
    "./public/js/**/*.js",
    "./styles/**/*.{css,js}",
    "./*.php"
  ],
  theme: {
    extend: {
      colors: {
        border: '#E5E7EB',
        input: '#E5E7EB',
        ring: '#F85606',
        background: '#FFFFFF',
        foreground: '#1F2937',
        
        primary: {
          DEFAULT: '#F85606',
          dark: '#E04E05',
          light: '#FF6B1F',
          50: '#FFF4F0',
          100: '#FFE9E0',
          200: '#FFD3C2',
          300: '#FFBDA3',
          500: '#F85606',
          600: '#E04E05',
          700: '#C74405',
          foreground: '#FFFFFF'
        },
        secondary: {
          DEFAULT: '#2D1B4E',
          dark: '#1F1235',
          light: '#3D2563',
          50: '#F5F3F7',
          100: '#EBE7EF',
          200: '#D7CFE0',
          500: '#2D1B4E',
          600: '#1F1235',
          foreground: '#FFFFFF'
        },
        accent: {
          DEFAULT: '#F85606',
          dark: '#E04E05',
          light: '#FF6B1F',
          foreground: '#FFFFFF'
        },
        muted: {
          DEFAULT: '#F3F4F6',
          foreground: '#6B7280'
        },
        card: {
          DEFAULT: '#FFFFFF',
          foreground: '#1F2937'
        },
        popover: {
          DEFAULT: '#FFFFFF',
          foreground: '#1F2937'
        },
        success: {
          DEFAULT: '#10B981',
          dark: '#059669',
          light: '#34D399',
          50: '#ECFDF5',
          500: '#10B981',
          600: '#059669',
          foreground: '#FFFFFF'
        },
        warning: {
          DEFAULT: '#F59E0B',
          dark: '#D97706',
          light: '#FBBF24',
          50: '#FFFBEB',
          500: '#F59E0B',
          600: '#D97706'
        },
        error: {
          DEFAULT: '#EF4444',
          dark: '#DC2626',
          light: '#F87171',
          50: '#FEF2F2',
          500: '#EF4444',
          600: '#DC2626'
        },
        destructive: {
          DEFAULT: '#EF4444',
          foreground: '#FFFFFF'
        },
        sale: {
          DEFAULT: '#F85606',
          dark: '#E04E05',
          light: '#FF6B1F',
          bg: '#FFF4F0',
          foreground: '#FFFFFF'
        },
        discount: {
          DEFAULT: '#10B981',
          dark: '#059669',
          light: '#34D399',
          bg: '#ECFDF5',
          foreground: '#FFFFFF'
        },
        info: {
          DEFAULT: '#3B82F6',
          dark: '#2563EB',
          light: '#60A5FA',
          50: '#EFF6FF',
          500: '#3B82F6',
          600: '#2563EB'
        },
        neutral: {
          50: '#FAFAFA',
          100: '#F5F5F5',
          200: '#E5E5E5',
          300: '#D4D4D4',
          400: '#A3A3A3',
          500: '#737373',
          600: '#525252',
          700: '#404040',
          800: '#262626',
          900: '#171717',
          950: '#0A0A0A'
        },
        daraz: {
          orange: '#F85606',
          purple: '#2D1B4E',
          yellow: '#FFD700'
        }
      },
      fontFamily: {
        'heading': ['Inter', 'system-ui', 'sans-serif'],
        'body': ['Inter', 'system-ui', 'sans-serif'],
        'display': ['Inter', 'system-ui', 'sans-serif']
      },
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1rem' }],
        'sm': ['0.875rem', { lineHeight: '1.25rem' }],
        'base': ['1rem', { lineHeight: '1.5rem' }],
        'lg': ['1.125rem', { lineHeight: '1.75rem' }],
        'xl': ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl': ['1.5rem', { lineHeight: '2rem' }],
        '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
        '5xl': ['3rem', { lineHeight: '1' }]
      },
      backgroundImage: {
        'gradient-primary': 'linear-gradient(135deg, #F85606 0%, #FF6B1F 100%)',
        'gradient-secondary': 'linear-gradient(135deg, #2D1B4E 0%, #3D2563 100%)',
        'gradient-hero': 'linear-gradient(135deg, #F85606 0%, #2D1B4E 100%)',
        'gradient-subtle': 'linear-gradient(180deg, #FFFFFF 0%, #F9FAFB 100%)'
      },
      boxShadow: {
        'sm': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        'DEFAULT': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)',
        'md': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
        'lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
        'xl': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
        'none': 'none'
      },
      borderRadius: {
        'sm': '0.25rem',
        'DEFAULT': '0.375rem',
        'md': '0.5rem',
        'lg': '0.75rem',
        'xl': '1rem',
        '2xl': '1.5rem'
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in',
        'slide-up': 'slideUp 0.4s ease-out',
        'scale-in': 'scaleIn 0.3s ease-out'
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        slideUp: {
          '0%': { transform: 'translateY(20px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' }
        },
        scaleIn: {
          '0%': { transform: 'scale(0.95)', opacity: '0' },
          '100%': { transform: 'scale(1)', opacity: '1' }
        }
      }
    }
  },
  plugins: [
    require("tailwindcss-animate")
  ]
};
