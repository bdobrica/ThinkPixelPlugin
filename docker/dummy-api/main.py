import json
import os
import random
from datetime import datetime, timedelta
from typing import Optional

import uvicorn
from fastapi import FastAPI, Header, HTTPException, Request
from fastapi.responses import JSONResponse
from rich.console import Console

console = Console()

app = FastAPI()

API_VERSION = os.getenv("API_VERSION", "0.1.0")
VALID_API_KEY = os.getenv("VALID_API_KEY", "my-secret-api-key")
REQUEST_SALT = os.getenv("REQUEST_SALT", "my-salt")


@app.middleware("http")
async def log_requests(request: Request, call_next):
    body = await request.body()
    headers = {k: v for k, v in request.headers.items()}

    console.print("[yellow]Headers:[/yellow]", headers, style="yellow")
    if body:
        console.print("[yellow]Body:[/yellow]\n", json.dumps(json.loads(body.decode()), indent=4), style="yellow")

    response = await call_next(request)

    if isinstance(response, JSONResponse) and isinstance(response.body, bytes):
        console.print("[green]Response:[/green]\n", json.dumps(response.body.decode(), indent=4), style="green")

    return response


@app.get("/ping")
async def ping():
    return {"status": "ok", "version": API_VERSION}


@app.post("/auth/token")
async def auth_token(x_api_key: str = Header(...)):
    if x_api_key != VALID_API_KEY:
        raise HTTPException(status_code=401, detail="Invalid API Key")

    token = "token123"
    expire_at = (datetime.now() + timedelta(hours=1)).isoformat()
    return {"token": token, "exp": expire_at}


@app.post("/search")
async def search(request: Request, authorization: Optional[str] = Header(None)):
    if not authorization or not authorization.startswith("Bearer"):
        raise HTTPException(status_code=401, detail="Unauthorized")

    _ = await request.json()
    console.print("[yellow]Bearer Token:[/yellow]", authorization, style="yellow")

    results = [
        {"id": 1, "score": 0.98, "text": "Example result 1"},
        {"id": 2, "score": 0.75, "text": "Example result 2"},
    ]
    return {"results": results}


@app.post("/store")
async def store(request: Request, authorization: Optional[str] = Header(None)):
    if not authorization or not authorization.startswith("Bearer"):
        raise HTTPException(status_code=401, detail="Unauthorized")

    body = await request.json()
    console.print("[yellow]Bearer Token:[/yellow]", authorization, style="yellow")

    num_texts = len(body.get("texts", []))
    timestamp = datetime.now().isoformat()
    return {
        "received_texts": num_texts,
        "stored_documents": num_texts,
        "timestamp": timestamp,
    }


@app.post("/register")
async def register():
    validation_token = f"validate-me-{random.randint(1000, 9999)}"
    return {
        "validation_token": validation_token,
        "validation_token_expires_at": (datetime.utcnow() + timedelta(minutes=5)).isoformat() + "Z",
        "message": "Validation initiated. Please respond to the challenge.",
    }


@app.post("/register/exchange")
async def register_exchange():
    api_key = "api-key-123"
    return {
        "api_key": api_key,
        "message": "Verification successful. Use the API key to access protected routes.",
    }


if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8080)
