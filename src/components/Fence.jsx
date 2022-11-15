import { Fragment } from 'react'
import Highlight, { defaultProps } from 'prism-react-renderer'

export function Fence({ children, language }) {
  return (
    <pre><code data-language='php'>
    {children.trimEnd()}
    </code>
    </pre>
  )
}
