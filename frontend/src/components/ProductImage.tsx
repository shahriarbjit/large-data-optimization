import { useState } from 'react'

interface Props {
  src: string
  alt: string
  className?: string
}

/** Product photo with lazy loading and a neutral placeholder if it fails. */
export function ProductImage({ src, alt, className }: Props) {
  const [failed, setFailed] = useState(false)

  if (failed) {
    return (
      <div className={`image-fallback ${className ?? ''}`} role="img" aria-label={alt}>
        No image
      </div>
    )
  }

  return <img src={src} alt={alt} className={className} loading="lazy" onError={() => setFailed(true)} />
}
