'use client'

import Image from 'next/image'
import { useState } from 'react'

type ProductImageProps = {
  src?: string | null
  alt: string
  sizes?: string
  className?: string
}

export default function ProductImage({ src, alt, sizes = '100vw', className = '' }: ProductImageProps) {
  const [failed, setFailed] = useState(false)
  const hasImage = Boolean(src && !failed)

  return (
    <div className={`product-image-frame${hasImage ? ' has-image' : ''}${className ? ` ${className}` : ''}`}>
      {hasImage ? (
        <Image
          src={src as string}
          alt={alt}
          fill
          sizes={sizes}
          unoptimized
          onError={() => setFailed(true)}
        />
      ) : (
        <div className="product-shape" aria-hidden="true" />
      )}
    </div>
  )
}
