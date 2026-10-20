import type { ReactNode } from 'react'
import './ui-icons-motion.css'
import './dark-gold.css'

export type IconName =
  | 'home' | 'grid' | 'cart' | 'user' | 'heart' | 'article' | 'book' | 'info' | 'chat' | 'close' | 'logout'
  | 'search' | 'arrow-left' | 'check' | 'trash' | 'truck' | 'leaf' | 'sprout' | 'box' | 'shield' | 'sparkle'
  | 'alert' | 'refresh' | 'lock' | 'plus' | 'minus' | 'inbox'

const PATHS: Record<IconName, ReactNode> = {
  home: <path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />,
  grid: <path d="M4 4h6.5v6.5H4zM13.5 4H20v6.5h-6.5zM4 13.5h6.5V20H4zM13.5 13.5H20V20h-6.5z" />,
  cart: (
    <>
      <path d="M3 4h2.2l2.2 11.1a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L19.8 8H6.1" />
      <circle cx="9.5" cy="19.5" r="1.3" />
      <circle cx="16.5" cy="19.5" r="1.3" />
    </>
  ),
  user: (
    <>
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21c0-3.9 3.6-6 8-6s8 2.1 8 6" />
    </>
  ),
  heart: <path d="M12 20.5S4 15.6 4 9.8A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 8 2.2c0 5.8-8 10.7-8 10.7z" />,
  article: (
    <>
      <path d="M6 3h8.5L19 7.5V21H6z" />
      <path d="M14 3v5h5M9 13h7M9 17h7" />
    </>
  ),
  book: (
    <>
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z" />
      <path d="M6.5 17H20v4H6.5A2.5 2.5 0 0 1 4 19.5" />
    </>
  ),
  info: (
    <>
      <circle cx="12" cy="12" r="9" />
      <path d="M12 11v5M12 8h.01" />
    </>
  ),
  chat: <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.1-4.6A8 8 0 1 1 21 12z" />,
  close: <path d="M6 6l12 12M18 6 6 18" />,
  logout: <path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9" />,
  search: (
    <>
      <circle cx="11" cy="11" r="6.5" />
      <path d="m20 20-4.2-4.2" />
    </>
  ),
  'arrow-left': <path d="M19 12H5M11 6l-6 6 6 6" />,
  check: <path d="M5 12.5l4.5 4.5L19 7.5" />,
  trash: <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />,
  truck: (
    <>
      <path d="M3 6h11v10H3zM14 9.5h4l3 3.5V16h-7" />
      <circle cx="7.5" cy="17.5" r="1.8" />
      <circle cx="17.5" cy="17.5" r="1.8" />
    </>
  ),
  leaf: (
    <>
      <path d="M20 4C10 4 5 9 5 15c0 2 .6 3.6 1.6 4.6C8 20.6 9.6 21 11 21c6 0 9-6 9-17z" />
      <path d="M5 21c2-5 5-8 10-11" />
    </>
  ),
  sprout: <path d="M12 21v-8M12 13c0-4 3-6 7-6 0 4-3 6-7 6zM12 15c0-3-2.5-5-6-5 0 3 2.5 5 6 5z" />,
  box: (
    <>
      <path d="M3.5 7.5 12 3l8.5 4.5v9L12 21l-8.5-4.5z" />
      <path d="M3.5 7.5 12 12l8.5-4.5M12 12v9" />
    </>
  ),
  shield: (
    <>
      <path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z" />
      <path d="m8.5 12 2.5 2.5 4.5-5" />
    </>
  ),
  sparkle: (
    <>
      <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z" />
      <path d="M19 17v4M17 19h4" />
    </>
  ),
  alert: (
    <>
      <circle cx="12" cy="12" r="9" />
      <path d="M12 7.5v5.5M12 16.5h.01" />
    </>
  ),
  refresh: <path d="M20 11a8 8 0 0 0-14.5-4M4 4v4h4M4 13a8 8 0 0 0 14.5 4M20 20v-4h-4" />,
  lock: (
    <>
      <rect x="5" y="11" width="14" height="9" rx="2" />
      <path d="M8 11V8a4 4 0 0 1 8 0v3" />
    </>
  ),
  plus: <path d="M12 5v14M5 12h14" />,
  minus: <path d="M5 12h14" />,
  inbox: (
    <>
      <path d="M4 13l2.5-7h11L20 13v6H4z" />
      <path d="M4 13h4.5a3.5 3.5 0 0 0 7 0H20" />
    </>
  ),
}

export function Icon({ name, size = 22, className }: { name: IconName; size?: number; className?: string }) {
  return (
    <svg
      className={`eco-icon eco-icon-${name}${className ? ` ${className}` : ''}`}
      viewBox="0 0 24 24"
      width={size}
      height={size}
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
    >
      {PATHS[name]}
    </svg>
  )
}

export default Icon
