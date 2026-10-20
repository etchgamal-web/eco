import { Icon } from '@/shared/components/Icon'
import type { IconName } from '@/shared/components/Icon'

const benefits: { icon: IconName; label: string }[] = [
  { icon: 'sparkle', label: 'منتجات يومية مدروسة' },
  { icon: 'sprout', label: 'مصادر مسؤولة' },
  { icon: 'box', label: 'تغليف أقل' },
  { icon: 'shield', label: 'جودة تدوم' },
]

export default function BenefitsStrip() {
  return (
    <section className="category-strip" id="story">
      {benefits.map((benefit) => (
        <span key={benefit.label}><Icon name={benefit.icon} size={20} />{benefit.label}</span>
      ))}
    </section>
  )
}
