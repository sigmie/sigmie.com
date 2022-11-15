import { Fragment } from 'react'
import Highlight, { defaultProps } from 'prism-react-renderer'

export function Fence({ children, language }) {
  return (
    <pre>
    {children.trimEnd()}
    </pre>
  )
}
