接口：招聘方 - 投递记录列表

GET /rc/companies/applications

说明：返回当前登录并切换到招聘方身份的企业收到的投递记录（分页）。

请求参数（Query）:
- per_page: 每页数量（默认 15）
- job_id: 可选，按职位过滤
- status: 可选，按投递状态过滤（数值）
- candidate_user_id: 可选，按候选人 user_id 过滤

响应示例（200）:
{
  "data": [
    {
      "id": 123,
      "company_id": 45,
      "job_id": 678,
      "candidate_user_id": 999,
      "resume_snapshot": { /* 简历快照 */ },
      "status": 1,
      "applied_at": "2026-07-14T10:00:00Z",
      /* 其它字段由 RcApplicationResource 提供 */
    }
  ],
  "meta": { /* 分页信息 */ }
}

错误示例（未绑定企业）:
{
  "code": 422,
  "message": "请先切换为招聘方身份并绑定企业。"
}
