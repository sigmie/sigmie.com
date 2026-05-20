import { createServer as createHttpServer } from "node:http";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { createServer } from "./server.mjs";

const PORT = process.env.MCP_PORT || 3100;

const httpServer = createHttpServer(async (req, res) => {
  if (req.method === "POST" && req.url === "/mcp") {
    const body = await new Promise((resolve) => {
      let data = "";
      req.on("data", (chunk) => (data += chunk));
      req.on("end", () => resolve(JSON.parse(data)));
    });

    const transport = new StreamableHTTPServerTransport({
      sessionIdGenerator: undefined,
    });

    const server = createServer();
    await server.connect(transport);
    await transport.handleRequest(req, res, body);
    return;
  }

  if (req.method === "GET" && req.url === "/health") {
    res.writeHead(200, { "Content-Type": "application/json" });
    res.end(JSON.stringify({ status: "ok" }));
    return;
  }

  res.writeHead(404);
  res.end();
});

httpServer.listen(PORT, "127.0.0.1", () => {
  console.log(`Sigmie MCP server listening on http://127.0.0.1:${PORT}/mcp`);
});
