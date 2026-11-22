import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { readdir } from "node:fs/promises";
import { join, basename, extname } from "node:path";

const DOCS_DIR = join(import.meta.dirname, "..", "docs");
const SITE_URL = "https://sigmie.com";
const SEARCH_URL = `${SITE_URL}/api/search/docs`;

// Version and page reach a file path, so both must be plain slugs.
const VERSION = z
  .string()
  .regex(/^v\d+$/, "A version looks like 'v2'.")
  .default("v2")
  .describe("Doc version, e.g. 'v2'");
const PAGE = z
  .string()
  .regex(/^[a-z0-9][a-z0-9-]*$/, "A page is a slug from list_docs, e.g. 'search'.")
  .describe("Page slug, e.g. 'installation', 'search'");

async function searchDocs(query) {
  const response = await fetch(SEARCH_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ query }),
  });

  if (!response.ok) {
    throw new Error(`Search API returned ${response.status}`);
  }

  return response.json();
}

async function listDocFiles(version) {
  const dir = join(DOCS_DIR, version);
  const files = await readdir(dir);

  return files
    .filter((f) => extname(f) === ".md")
    .map((f) => basename(f, ".md"));
}

// The site serves each page as agents should read it (Torchlight
// annotations removed, links absolute), so read_doc returns that.
async function readDoc(page, version) {
  const response = await fetch(`${SITE_URL}/docs/${version}/${page}.md`);

  if (!response.ok) {
    throw new Error(`No ${version} page named "${page}". Call list_docs for the page names.`);
  }

  return response.text();
}

export function createServer() {
  const server = new McpServer({
    name: "sigmie-docs",
    version: "1.0.0",
  });

  server.tool(
    "search_docs",
    "Semantic search across Sigmie documentation. Returns the most relevant sections matching the query.",
    { query: z.string().max(500).describe("Search query") },
    async ({ query }) => {
      const { results, total } = await searchDocs(query);

      const text = results
        .map(
          (r, i) =>
            `## ${i + 1}. ${r.title}\n` +
            `**Page:** ${r.page} (${r.version}) — ${r.url}\n\n` +
            `${r.content}\n`
        )
        .join("\n---\n\n");

      return {
        content: [
          { type: "text", text: `Found ${total} results.\n\n${text}` },
        ],
      };
    }
  );

  server.tool(
    "read_doc",
    "Read the full content of a documentation page by its slug.",
    { page: PAGE, version: VERSION },
    async ({ page, version }) => {
      const content = await readDoc(page, version);

      return {
        content: [{ type: "text", text: content }],
      };
    }
  );

  server.tool(
    "list_docs",
    "List all available documentation pages for a given version.",
    { version: VERSION },
    async ({ version }) => {
      const pages = await listDocFiles(version);

      return {
        content: [
          {
            type: "text",
            text: `Available ${version} docs:\n\n${pages.map((p) => `- ${p}`).join("\n")}`,
          },
        ],
      };
    }
  );

  return server;
}
