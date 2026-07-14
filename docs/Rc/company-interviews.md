接口：招聘方 - 面试日程列表

GET /rc/companies/interviews

说明：返回当前招聘方企业相关的面试安排（分页），含面试详情与对应投递记录。

请求参数（Query）:
- per_page: 每页数量（默认 15）
- status: 面试状态（可选）
- job_id: 按职位过滤（可选）
- interview_at_from: 面试开始时间范围（ISO8601，含）
- interview_at_to: 面试结束时间范围（ISO8601，含）

响应示例（200）:
{
  "data": [
    {
      "id": 10,
      "interview_at": "2026-07-20T14:00:00Z",
      "duration_mins": 30,
      "mode": 1,
      "interviewer_name": "张三",
      "location": "公司总部",
      "status": 2,
      "application": {
        "id": 123,
        "job_id": 678,
        "candidate_user_id": 999,
        "resume_snapshot": { /* 简历快照 */ }
      }
    }
  ],
  "meta": { /* 分页信息 */ }
}

错误示例（未绑定企业）:
{
  "code": 422,
  "message": "请先切换为招聘方身份并绑定企业。"
}
