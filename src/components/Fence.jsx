import { Fragment } from 'react'

export function Fence({ children, language }) {
  return (
    <pre><code data-language='php'>
    {children.trimEnd()}
    </code>
    </pre>
  )
}
