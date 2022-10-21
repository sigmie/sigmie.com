import { Fragment, useState } from 'react'
import { Combobox, Dialog, Transition } from '@headlessui/react'
import { SearchIcon } from '@heroicons/react/solid'
import { ExclamationIcon } from '@heroicons/react/outline'
import axios from 'axios'
import { useRouter } from 'next/router'

function classNames(...classes) {
  return classes.filter(Boolean).join(' ')
}

export default function SearchResults({ onClose, isOpen }) {
  const [open, setOpen] = useState(true)
  const [rawQuery, setRawQuery] = useState('')
  const [results, setResults] = useState([])
  const router = useRouter()

  function onSearchResult(path) {
    router.push('/' + path)
    onClose()
  }

  async function onSearch(e) {
    e.preventDefault()
    let value = e.target.value

    const instance = axios.create({
      baseURL: 'https://skiukk4yaxfg8vkoj.sigmie.app',
      timeout: 1000,
      headers: {
        'Content-Type': 'application/json',
        'X-Sigmie-API-Key': 'V0oECAz7T1osnXxCK5hDxxGclFWp1ohO9LfvOWUo',
        'X-Sigmie-Application': 'skiukk4yaxfg8vkoj',
      },
    })

    let res = await instance.post('/v1/search/lib-docs', {
      query: value,
      per_page: 10,
    })

    setResults(Object.values(res.data.hits))
  }

  return (
    <Transition.Root
      show={isOpen}
      as={Fragment}
      afterLeave={() => setRawQuery('')}
      appear
    >
      <Dialog as="div" className="relative z-50" onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity" />
        </Transition.Child>

        <div className="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20">
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0 scale-95"
            enterTo="opacity-100 scale-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100 scale-100"
            leaveTo="opacity-0 scale-95"
          >
            <Dialog.Panel className="mx-auto max-w-xl transform divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all dark:divide-gray-600">
              <Combobox onChange={(path) => onSearchResult(path)}>
                <div className="relative">
                  <SearchIcon
                    className="pointer-events-none absolute top-3.5 left-4 h-5 w-5 text-gray-400"
                    aria-hidden="true"
                  />
                  <Combobox.Input
                    className="h-12 w-full rounded-t-xl border-0 bg-transparent  pl-11 pr-4 text-gray-800 placeholder-gray-400 outline-0 focus:ring-0 dark:border-gray-500 dark:bg-black dark:text-gray-200 sm:text-sm"
                    placeholder="Search..."
                    onChange={(event) => onSearch(event)}
                  />
                </div>

                {results.length > 0 && (
                  <Combobox.Options
                    static
                    className="max-h-[800px] scroll-py-10 scroll-pb-2 space-y-4 overflow-y-auto p-4 pb-2 dark:bg-black "
                  >
                    {
                      <li>
                        <h2 className="text-xs font-semibold text-gray-900 dark:text-gray-200 ">
                          Hits
                        </h2>
                        <ul className="-mx-4 mt-2 text-sm text-gray-700 dark:text-gray-300">
                          {results.map((hit) => (
                            <Combobox.Option
                              key={hit._id}
                              value={hit.path}
                              className={({ active }) =>
                                classNames(
                                  'flex cursor-default select-none items-center px-4 py-2',
                                  active && 'bg-gray-900 text-white'
                                )
                              }
                            >
                              {({ active }) => (
                                <>
                                  <div className="flex flex-col space-y-2">
                                    <div className="flex flex-row font-bold">
                                      {hit.title}
                                    </div>
                                    <div className="flex flex-row">
                                      <div>
                                        {Object.entries(hit._highlight).map(
                                          ([attribute, value], i) => (
                                            <div key={attribute}>
                                              <span>...</span>{' '}
                                              <span
                                                dangerouslySetInnerHTML={{
                                                  __html: value,
                                                }}
                                              ></span>
                                            </div>
                                          )
                                        )}
                                      </div>
                                    </div>
                                  </div>
                                </>
                              )}
                            </Combobox.Option>
                          ))}
                        </ul>
                      </li>
                    }
                  </Combobox.Options>
                )}

                {results.length === 0 && (
                  <div className="py-14 px-6 text-center text-sm dark:bg-black dark:text-gray-200 sm:px-14">
                    <ExclamationIcon
                      className="mx-auto h-6 w-6 text-gray-400"
                      aria-hidden="true"
                    />
                    <p className="mt-4 font-semibold text-gray-900 dark:text-gray-300">
                      No Results
                    </p>
                    <p className="mt-2 text-gray-500">
                      Enter a search term to find results in the documentation.
                    </p>
                  </div>
                )}

                <div className="flex flex-wrap items-center bg-gray-50 py-2.5 px-4 text-xs text-gray-700 dark:bg-black dark:text-gray-300">
                  Type{' '}
                  <kbd
                    className={classNames(
                      'mx-1 flex h-5 w-8 items-center justify-center rounded border bg-white font-semibold dark:border-gray-500 dark:bg-gray-700 dark:text-white sm:mx-2',
                      rawQuery.startsWith('#')
                        ? 'border-indigo-600 text-indigo-600'
                        : 'border-gray-400 text-gray-900'
                    )}
                  >
                    <svg
                      width="15"
                      height="15"
                      aria-label="Enter key"
                      role="img"
                    >
                      <g
                        fill="none"
                        stroke="currentColor"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="1.2"
                      >
                        <path d="M12 3.53088v3c0 1-1 2-2 2H4M7 11.53088l-3-3 3-3"></path>
                      </g>
                    </svg>
                  </kbd>{' '}
                  to select
                  <span className=""></span>
                  <div className="mx-1 flex flex-row space-x-0.5">
                    <kbd
                      className={classNames(
                        'mx-1 flex h-5 w-8 items-center justify-center rounded border bg-white font-semibold dark:border-gray-500 dark:bg-gray-700 dark:text-white sm:mx-2',
                        rawQuery.startsWith('>')
                          ? 'border-indigo-600 text-indigo-600'
                          : 'border-gray-400 text-gray-900'
                      )}
                    >
                      <span>
                        <svg
                          width="15"
                          height="15"
                          aria-label="Arrow down"
                          role="img"
                        >
                          <g
                            fill="none"
                            stroke="currentColor"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="1.2"
                          >
                            <path d="M7.5 3.5v8M10.5 8.5l-3 3-3-3"></path>
                          </g>
                        </svg>
                      </span>
                    </kbd>{' '}
                    <kbd
                      className={classNames(
                        'mx-1 flex h-5 w-8 items-center justify-center rounded border bg-white font-semibold dark:border-gray-500 dark:bg-gray-700 dark:text-white sm:mx-2',
                        rawQuery.startsWith('>')
                          ? 'border-indigo-600 text-indigo-600'
                          : 'border-gray-400 text-gray-900'
                      )}
                    >
                      <span>
                        <svg
                          width="15"
                          height="15"
                          aria-label="Arrow up"
                          role="img"
                        >
                          <g
                            fill="none"
                            stroke="currentColor"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="1.2"
                          >
                            <path d="M7.5 11.5v-8M10.5 6.5l-3-3-3 3"></path>
                          </g>
                        </svg>
                      </span>
                    </kbd>
                  </div>{' '}
                  to navigate{' '}
                  <kbd
                    className={classNames(
                      'mx-1 flex h-5 w-8 items-center justify-center rounded border bg-white font-semibold dark:border-gray-500 dark:bg-gray-700 dark:text-white sm:mx-2',
                      rawQuery === '?'
                        ? 'border-gray-600 text-gray-600'
                        : 'border-gray-400 text-gray-900'
                    )}
                  >
                    esc
                  </kbd>{' '}
                  to close.
                </div>
              </Combobox>
            </Dialog.Panel>
          </Transition.Child>
        </div>
      </Dialog>
    </Transition.Root>
  )
}
