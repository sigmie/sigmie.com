import { describe, it } from "node:test";
import assert from "node:assert/strict";
import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";

async function createClient() {
  const transport = new StdioClientTransport({
    command: "node",
    args: ["index.mjs"],
    cwd: import.meta.dirname,
  });

  const client = new Client({ name: "test-client", version: "1.0.0" });
  await client.connect(transport);

  return client;
}

describe("sigmie-docs MCP server", () => {
  it("lists available tools", async () => {
    const client = await createClient();

    const { tools } = await client.listTools();
    const names = tools.map((t) => t.name);

    assert.ok(names.includes("search_docs"));
    assert.ok(names.includes("read_doc"));
    assert.ok(names.includes("list_docs"));
    assert.equal(tools.length, 3);

    await client.close();
  });

  it("list_docs returns v2 doc pages", async () => {
    const client = await createClient();

    const result = await client.callTool({
      name: "list_docs",
      arguments: { version: "v2" },
    });

    const text = result.content[0].text;
    assert.ok(text.includes("Available v2 docs:"));
    assert.ok(text.includes("installation"));
    assert.ok(text.includes("search"));

    await client.close();
  });

  it("read_doc returns full page content", async () => {
    const client = await createClient();

    const result = await client.callTool({
      name: "read_doc",
      arguments: { page: "installation", version: "v2" },
    });

    const text = result.content[0].text;
    assert.ok(text.length > 100, "Doc content should be substantial");
    assert.ok(
      text.toLowerCase().includes("install"),
      "Installation doc should mention install"
    );

    await client.close();
  });

  it("read_doc fails gracefully for missing page", async () => {
    const client = await createClient();

    const result = await client.callTool({
      name: "read_doc",
      arguments: { page: "nonexistent-page-xyz", version: "v2" },
    });

    assert.ok(result.isError);

    await client.close();
  });

  it("search_docs returns results from semantic search API", async () => {
    const client = await createClient();

    const result = await client.callTool({
      name: "search_docs",
      arguments: { query: "how to create an index" },
    });

    const text = result.content[0].text;
    assert.ok(text.includes("Found"), "Should report result count");
    assert.ok(text.includes("##"), "Should contain formatted results");

    await client.close();
  });
});
