import { Fragment, createElement, useRef, useState, useEffect } from 'react'
import remark from 'remark'
import torchlight from 'remark-torchlight'
import {unified} from 'unified'
import remarkParse from 'remark-parse'
import remarkRehype from 'remark-rehype'
import rehypeSanitize from 'rehype-sanitize'
import rehypeStringify from 'rehype-stringify'



export function Fence({ children, language }) {
  const [code, setCode] = useState(children.trimEnd());

  useEffect(() => {
    // React advises to declare the async function directly inside useEffect
    async function getToken() {

    const code = await unified()
    .use(remarkParse)
    .use(remarkRehype)
    .use(rehypeSanitize)
    .use(rehypeStringify)
        .use(torchlight,{
            // All API configuration goes under `config`.
            config: {
                token: 'torch_yI6tX9DXX3maZWxoTd03SXVgDyG41uMUUHXpGsQI',
                theme: 'material-theme-palenight'
            }
        })
    .process('```php $foo = false ```')
    // .process(children.trimEnd());
        //  let code = await unified()
        // .use(html)
        //  .use(remarkParse)
        // .process('---\nlayout: home\n---\n\n# Hi ~~Mars~~Venus!')
        // .toString()

        setCode(String(code));
    };

    getToken();

  }, []);

  return (
    <pre><code data-language='php'>
    {code}
    </code>
    </pre>
  )
}

function useStaticContent() {
  const ref = useRef(null)
  const [render, setRender] = useState(typeof window === 'undefined')

  useEffect(() => {
    // check if the innerHTML is empty as client side navigation
    // need to render the component without server-side backup
    const isEmpty = ref.current.innerHTML === ''
    if (isEmpty) {
      setRender(true)
    }
  }, [])

  return [render, ref]
}

export default function StaticContent({ children, element = 'div', ...props }) {
  const [render, ref] = useStaticContent()

  // if we're in the server or a spa navigation, just render it
  if (render) {
    return createElement(element,
      props,
      children,
    )
  }

  // avoid re-render on the client
  return createElement(element, {
    ...props,
    ref,
    suppressHydrationWarning: true,
    dangerouslySetInnerHTML: { __html: '' },
  })
}
